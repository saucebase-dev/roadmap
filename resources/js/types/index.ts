export type RoadmapStatus = Modules.Roadmap.Enums.RoadmapStatus;

export type RoadmapType = Modules.Roadmap.Enums.RoadmapType;

export type RoadmapItem = Modules.Roadmap.Data.RoadmapItemData;

export type RoadmapComment = Modules.Roadmap.Data.RoadmapCommentData;

export type RoadmapItemDetail = RoadmapItem & Modules.Roadmap.Data.RoadmapItemDetailData;

export interface RoadmapColumn {
    value: RoadmapStatus;
    label: string;
}

export interface RoadmapTypeOption {
    value: RoadmapType;
    label: string;
    color: string;
}
