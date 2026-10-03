import { registerIcon } from '@/lib/navigation';
import IconMap from '~icons/heroicons/map';

export function setup() {
    registerIcon('roadmap', IconMap);
}

/**
 * Roadmap module after mount logic
 * Called after the app has been mounted
 */
export function afterMount() { }
