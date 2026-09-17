import { expect, test } from '@e2e/fixtures';

const clean = 'Modules\\Roadmap\\Tests\\Support\\RoadmapTestHelper::clean';
const itemModel = 'Modules\\Roadmap\\Models\\RoadmapItem';

test.describe('Roadmap', () => {
    // Every test seeds the same tables, so they cannot share a worker.
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ laravel }) => {
        await laravel.callFunction(clean);
    });

    test('the board shows public items by status and hides the rest', async ({
        page,
        laravel,
    }) => {
        await laravel.factory(itemModel, {
            title: 'Planned item',
            status: 'planned',
            type: 'feature',
        });
        await laravel.factory(itemModel, {
            title: 'Building item',
            status: 'in_progress',
            type: 'feature',
        });
        await laravel.factory(itemModel, {
            title: 'Shipped item',
            status: 'shipped',
            type: 'feature',
        });
        await laravel.factory(itemModel, {
            title: 'Backlog item',
            status: 'backlog',
            type: 'feature',
        });
        await laravel.factory(itemModel, {
            title: 'Waiting for review',
            status: 'under_review',
            type: 'feature',
        });
        await laravel.factory(itemModel, {
            title: 'Turned down',
            status: 'closed',
            type: 'feature',
        });

        // A guest reads the board: it is a public page, not behind login.
        await page.goto('/roadmap');

        await expect(
            page
                .getByTestId('roadmap-column-planned')
                .getByText('Planned item'),
        ).toBeVisible();
        await expect(
            page
                .getByTestId('roadmap-column-in_progress')
                .getByText('Building item'),
        ).toBeVisible();
        await expect(
            page
                .getByTestId('roadmap-column-shipped')
                .getByText('Shipped item'),
        ).toBeVisible();
        await expect(
            page.getByTestId('roadmap-backlog').getByText('Backlog item'),
        ).toBeVisible();

        await expect(page.getByText('Waiting for review')).toHaveCount(0);
        await expect(page.getByText('Turned down')).toHaveCount(0);
    });

    test('a guest who votes is sent to login', async ({ page, laravel }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Guest vote',
            status: 'planned',
            type: 'feature',
        });

        await page.goto('/roadmap');
        await page.getByTestId(`vote-btn-${item.id}`).click();

        await expect(page).toHaveURL(/login/);
    });

    test('voting updates the count in place and toggles off again', async ({
        page,
        laravel,
        credentials,
        loginAs,
    }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Votable item',
            status: 'planned',
            type: 'feature',
        });

        await loginAs(credentials.user);
        await page.goto('/roadmap');

        const button = page.getByTestId(`vote-btn-${item.id}`);
        const count = page.getByTestId(`vote-count-${item.id}`);
        await expect(count).toHaveText('0');

        // Survives Inertia navigation, wiped by a full page load.
        await page.evaluate(() => {
            (window as unknown as { __noReload: boolean }).__noReload = true;
        });

        await button.click();
        await expect(count).toHaveText('1');
        await expect(button).toHaveAttribute('data-voted', 'true');

        await button.click();
        await expect(count).toHaveText('0');
        await expect(button).toHaveAttribute('data-voted', 'false');

        expect(
            await page.evaluate(
                () =>
                    (window as unknown as { __noReload?: boolean }).__noReload,
            ),
        ).toBe(true);
    });

    test('an item opens in a slideover from the board and closes back to it', async ({
        page,
        laravel,
    }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Openable item',
            status: 'planned',
            type: 'feature',
        });

        await page.goto('/roadmap');
        await page.getByTestId(`roadmap-item-link-${item.id}`).click();

        const panel = page.getByTestId('roadmap-item-modal');
        await expect(panel).toBeVisible();
        await expect(page).toHaveURL(new RegExp(`/roadmap/${item.slug}$`));

        await page.keyboard.press('Escape');
        await expect(panel).toHaveCount(0);
        await expect(page).toHaveURL(/\/roadmap$/);
    });

    test('the item URL opens as a full page with a canonical link', async ({
        page,
        laravel,
    }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Shared link item',
            status: 'planned',
            type: 'feature',
        });

        await page.goto(`/roadmap/${item.slug}`);

        // Typed or shared links must render the page, not the slideover, so that
        // search engines and the sitemap see ordinary HTML.
        await expect(page.getByTestId('roadmap-item-modal')).toHaveCount(0);
        await expect(page.getByTestId('back-to-roadmap')).toBeVisible();
        await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
            'href',
            new RegExp(`/roadmap/${item.slug}$`),
        );

        await page.getByTestId('back-to-roadmap').click();
        await expect(page).toHaveURL(/\/roadmap$/);
    });

    test('a signed-in visitor comments in the slideover and sees it appear', async ({
        page,
        laravel,
        credentials,
        loginAs,
    }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Discussed item',
            status: 'planned',
            type: 'feature',
        });

        await loginAs(credentials.user);
        await page.goto('/roadmap');
        await page.getByTestId(`roadmap-item-link-${item.id}`).click();

        const panel = page.getByTestId('roadmap-item-modal');
        await expect(panel).toBeVisible();

        await panel.getByTestId('comment-body').fill('Please build this next');
        await panel.getByTestId('comment-submit').click();

        // The redirect refreshes the board behind the panel, so the panel has to
        // ask for its own props again for the comment to show.
        await expect(panel.getByText('Please build this next')).toBeVisible();
    });

    test('a guest is asked to log in before commenting', async ({
        page,
        laravel,
    }) => {
        const item = await laravel.factory(itemModel, {
            title: 'Read only item',
            status: 'planned',
            type: 'feature',
        });

        await page.goto(`/roadmap/${item.slug}`);

        await expect(page.getByTestId('comment-login')).toBeVisible();
        await expect(page.getByTestId('comment-body')).toHaveCount(0);
    });

    test('a suggestion is submitted and waits for review under my feedback', async ({
        page,
        laravel,
        credentials,
        loginAs,
    }) => {
        // The board only renders once something public exists.
        await laravel.factory(itemModel, {
            title: 'Existing planned item',
            status: 'planned',
            type: 'feature',
        });

        await loginAs(credentials.user);
        await page.goto('/roadmap');

        await page.getByTestId('suggest-btn').click();
        await page.getByTestId('suggest-title').fill('Export to CSV');
        await page.getByTestId('suggest-submit-btn').click();

        // New suggestions wait for review, so the board must not show it yet.
        await expect(page).toHaveURL(/\/roadmap$/);
        await expect(page.getByTestId('roadmap-board')).not.toContainText(
            'Export to CSV',
        );

        // Its author still finds it, with the status spelled out.
        await page.getByTestId('filter-mine').click();
        const mine = page.getByTestId('roadmap-mine');
        await expect(mine).toContainText('Export to CSV');
        await expect(mine).toContainText('Under Review');

        await page.getByTestId('filter-mine-back').click();
        await expect(page.getByTestId('roadmap-board')).toBeVisible();
    });

    test('sorting the backlog reorders it without reloading the page', async ({
        page,
        laravel,
        credentials,
        loginAs,
    }) => {
        const older = await laravel.factory(itemModel, {
            title: 'Older backlog item',
            status: 'backlog',
            type: 'feature',
            created_at: new Date(Date.now() - 86_400_000).toISOString(),
        });
        const newer = await laravel.factory(itemModel, {
            title: 'Newer backlog item',
            status: 'backlog',
            type: 'feature',
        });

        await loginAs(credentials.user);
        await page.goto('/roadmap');

        // Trending is the default, so the voted item leads.
        await page.getByTestId(`vote-btn-${older.id}`).click();
        await expect(page.getByTestId(`vote-count-${older.id}`)).toHaveText(
            '1',
        );

        const titles = () =>
            page
                .getByTestId('roadmap-backlog')
                .locator('[data-testid^="roadmap-item-link-"]')
                .allInnerTexts();

        expect(await titles()).toEqual([
            'Older backlog item',
            'Newer backlog item',
        ]);

        // Survives Inertia navigation, wiped by a full page load.
        await page.evaluate(() => {
            (window as unknown as { __noReload: boolean }).__noReload = true;
        });

        await page.getByTestId('sort-trigger').click();
        await page.getByTestId('sort-new').click();

        await expect
            .poll(titles)
            .toEqual(['Newer backlog item', 'Older backlog item']);

        expect(
            await page.evaluate(
                () =>
                    (window as unknown as { __noReload?: boolean }).__noReload,
            ),
        ).toBe(true);
        expect(newer.id).toBeTruthy();
    });
});
