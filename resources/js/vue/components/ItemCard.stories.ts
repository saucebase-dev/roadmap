import preview from '@/.storybook/preview';
import { expect, fn } from 'storybook/test';
import ItemCard from './ItemCard.vue';

/**
 * An item on the roadmap's board: its vote, title, description, type, and comment count.
 * The title opens the item in a slideover. `vote` asks whoever shows it to count a vote.
 */
const meta = preview.meta({
    title: 'Roadmap/ItemCard',
    component: ItemCard,
    args: {
        item: {
            id: 7,
            title: 'Dark mode for the dashboard',
            slug: 'dark-mode-for-the-dashboard',
            url: '/roadmap/dark-mode-for-the-dashboard',
            description:
                'Follow the system setting, with a switch in the user menu to override it.',
            status: 'planned',
            status_label: 'Planned',
            type: 'feature',
            type_label: 'Feature',
            votes_count: 42,
            comments_count: 5,
            has_voted: false,
            votable: true,
            created_at: '2026-11-02T09:00:00Z',
        },
        typeVariant: 'default',
        commentsEnabled: true,
        showStatus: false,
        votePending: false,
    },
    decorators: [() => ({ template: '<div class="max-w-sm"><story /></div>' })],
});
export default meta;

/** On the board, in its status's column. Its vote button asks for a vote. */
export const Default = meta.story({
    // The spy here only: in the meta's args it would leave every snippet incomplete.
    args: { onVote: fn() },
    play: async ({ args, canvas, userEvent }) => {
        await expect(
            canvas.getByRole('link', { name: 'Dark mode for the dashboard' }),
        ).toBeVisible();
        await expect(
            canvas.getByRole('link', { name: 'Comments' }),
        ).toHaveTextContent('5');

        await userEvent.click(canvas.getByRole('button', { name: 'Upvote' }));

        await expect(args.onVote).toHaveBeenCalled();
    },
});

/** In the visitor's own submissions, off the board: it says its status. */
export const WithStatus = meta.story({
    args: { showStatus: true },
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Planned')).toBeVisible();
    },
});

/** Comments turned off: no count. */
export const WithoutComments = meta.story({
    args: { commentsEnabled: false },
    play: async ({ canvas }) => {
        await expect(
            canvas.queryByRole('link', { name: 'Comments' }),
        ).toBeNull();
    },
});

/** A bug the visitor voted for, with no description. */
export const VotedBug = meta.story({
    args: {
        item: {
            id: 8,
            title: 'Invoices show the wrong currency',
            slug: 'invoices-wrong-currency',
            url: '/roadmap/invoices-wrong-currency',
            description: null,
            status: 'in_progress',
            status_label: 'In progress',
            type: 'bug',
            type_label: 'Bug',
            votes_count: 12,
            comments_count: 0,
            has_voted: true,
            votable: true,
            created_at: '2026-11-18T09:00:00Z',
        },
        typeVariant: 'destructive',
    },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Upvote' }),
        ).toHaveAttribute('aria-pressed', 'true');
    },
});
