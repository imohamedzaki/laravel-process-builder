import type { NodeProps } from '@xyflow/react';

export interface LaneBandNodeData extends Record<string, unknown> {
    name: string;
    color: string | null;
    height: number;
}

export const LANE_BAND_WIDTH = 4000;

export function LaneBandNode({ data }: NodeProps): JSX.Element {
    const bandData = data as LaneBandNodeData;

    return (
        <div
            className="pb-lane-band"
            style={{
                width: LANE_BAND_WIDTH,
                height: bandData.height,
                background: bandData.color ?? undefined,
            }}
        />
    );
}
