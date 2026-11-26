import preview from '@/.storybook/preview';
import { expect, fn } from 'storybook/test';
import VoteButton from './VoteButton.vue';

/**
 * An item's upvote and its count. Filled once the visitor has voted. Whoever shows it
 * counts the vote, on `vote`; `pending` disables it meanwhile, or for an item that takes
 * no votes.
 */
const meta = preview.meta({
    title: 'Roadmap/VoteButton',
    component: VoteButton,
    args: { itemId: 1, votes: 42, voted: false, pending: false },
});
export default meta;

/** Not voted yet. A click asks for the vote. */
export const Default = meta.story({
    // The spy here only: in the meta's args it would leave every snippet incomplete.
    args: { onVote: fn() },
    play: async ({ args, canvas, userEvent }) => {
        const button = canvas.getByRole('button', { name: 'Upvote' });
        await expect(button).toHaveAttribute('aria-pressed', 'false');

        await userEvent.click(button);

        await expect(args.onVote).toHaveBeenCalled();
    },
});

/** The visitor's vote counts: filled. */
export const Voted = meta.story({
    args: { voted: true, votes: 43 },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Upvote' }),
        ).toHaveAttribute('aria-pressed', 'true');
    },
});

/** A vote on its way, or an item that takes none: disabled. */
export const Pending = meta.story({
    args: { pending: true },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Upvote' }),
        ).toBeDisabled();
    },
});
