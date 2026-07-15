import { useState } from 'react';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { LaneActorType } from '@/types/process';

let laneIdCounter = 0;

function nextLaneId(): string {
    laneIdCounter += 1;

    return `lane_${Date.now()}_${laneIdCounter}`;
}

export function LaneManager(): JSX.Element {
    const { lanes, addLane, updateLane, removeLane } = useProcessEditorStore();
    const [isOpen, setIsOpen] = useState(false);

    const sortedLanes = [...lanes].sort((a, b) => a.order - b.order);

    function handleAddLane(): void {
        addLane({
            id: nextLaneId(),
            name: `Lane ${lanes.length + 1}`,
            actorType: 'human',
            order: lanes.length,
            color: null,
        });
    }

    return (
        <div className="pb-lane-manager">
            <button type="button" onClick={() => setIsOpen((open) => !open)}>
                Lanes ({lanes.length})
            </button>

            {isOpen && (
                <div className="pb-lane-manager-panel">
                    {sortedLanes.map((lane) => (
                        <div key={lane.id} className="pb-lane-manager-row">
                            <input
                                type="text"
                                value={lane.name}
                                onChange={(event) => updateLane(lane.id, { name: event.target.value })}
                                aria-label="Lane name"
                            />
                            <select
                                value={lane.actorType ?? ''}
                                onChange={(event) =>
                                    updateLane(lane.id, {
                                        actorType: (event.target.value || null) as LaneActorType,
                                    })
                                }
                                aria-label="Lane actor type"
                            >
                                <option value="human">Human</option>
                                <option value="system">System</option>
                                <option value="">None</option>
                            </select>
                            <button type="button" onClick={() => removeLane(lane.id)} aria-label={`Remove ${lane.name}`}>
                                Remove
                            </button>
                        </div>
                    ))}

                    <button type="button" onClick={handleAddLane}>
                        Add lane
                    </button>
                </div>
            )}
        </div>
    );
}
