import { useT } from '@/i18n';
import SiteLayout from '@/layouts/SiteLayout';
import { Link } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';

import type { RoadmapItemDetail } from '../../types';
import ItemDetail from '../components/ItemDetail';

import IconArrowLeft from '~icons/heroicons/arrow-left';

/**
 * One roadmap item, as a page or as a modal over the board.
 *
 * The URL is the same either way. The controller passes `modal` only when the
 * board asked for one, so a typed or shared link still opens the full page and
 * search engines see ordinary HTML.
 */
export default function Show({
    item,
    authenticated,
    modal,
}: {
    item: RoadmapItemDetail;
    authenticated: boolean;
    modal?: boolean;
}) {
    const t = useT();

    if (modal) {
        return (
            <Modal slideover position="right" maxWidth="2xl">
                <div className="p-6" data-testid="roadmap-item-modal">
                    <ItemDetail item={item} authenticated={authenticated} />
                </div>
            </Modal>
        );
    }

    return (
        <SiteLayout
            title={item.title}
            description={item.description ?? undefined}
            canonical={item.url}
            type="article"
        >
            <div className="mx-auto w-full max-w-3xl px-6 py-16">
                <Link
                    href={route('roadmap.index')}
                    data-testid="back-to-roadmap"
                    className="text-muted-foreground hover:text-foreground mb-8 inline-flex items-center gap-2 text-sm"
                >
                    <IconArrowLeft className="size-4" />
                    {t('Back to roadmap')}
                </Link>

                <ItemDetail item={item} authenticated={authenticated} />
            </div>
        </SiteLayout>
    );
}
