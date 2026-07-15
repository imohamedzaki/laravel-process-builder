import { useViewport } from '@xyflow/react';
import { LANE_BAND_HEIGHT } from '@/nodes/LaneBandNode';
import type { ProcessLane } from '@/types/process';

interface SwimlaneHeadersProps {
    lanes: ProcessLane[];
}

const LANE_HEADER_WIDTH = 160;

export function SwimlaneHeaders({ lanes }: SwimlaneHeadersProps): JSX.Element | null {
    const { y: viewportY, zoom } = useViewport();

    if (lanes.length === 0) {
        return null;
    }

    const sortedLanes = [...lanes].sort((a, b) => a.order - b.order);

    return (
        <div className="pb-lane-headers" style={{ width: LANE_HEADER_WIDTH }}>
            {sortedLanes.map((lane, index) => {
                const top = viewportY + index * LANE_BAND_HEIGHT * zoom;
                const height = LANE_BAND_HEIGHT * zoom;

                return (
                    <div
                        key={lane.id}
                        className="pb-lane-header"
                        style={{ top, height, background: lane.color ?? undefined }}
                    >
                        <span className="pb-lane-header-icon" aria-hidden="true">
                            {lane.actorType === 'system' ? '⚙' : '☺'}
                        </span>
                        <span className="pb-lane-header-name">{lane.name}</span>
                    </div>
                );
            })}
        </div>
    );
}
