import { registerIcon } from '@/lib/navigation';
import IconMap from '~icons/heroicons/map';

export function setup() {
    registerIcon('roadmap', IconMap);
}

export function afterMount() {}
