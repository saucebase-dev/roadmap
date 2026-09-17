export type RoadmapStatus = Modules.Roadmap.Enums.RoadmapStatus;

export type RoadmapType = Modules.Roadmap.Enums.RoadmapType;

export interface RoadmapItem {
    id: number;
    title: string;
    slug: string;
    url: string;
    description: string | null;
    status: RoadmapStatus;
    status_label: string;
    type: RoadmapType;
    type_label: string;
    votes_count: number;
    comments_count: number;
    has_voted: boolean;
    created_at: string;
}

export interface RoadmapComment {
    id: number;
    body: string;
    created_at: string;
    author: string;
    mine: boolean;
}

export interface RoadmapItemDetail extends RoadmapItem {
    official_response: string | null;
    official_response_at: string | null;
    comments_enabled: boolean;
    comments: RoadmapComment[];
}

export interface RoadmapColumn {
    value: RoadmapStatus;
    label: string;
}

export interface RoadmapTypeOption {
    value: RoadmapType;
    label: string;
    color: string;
}
