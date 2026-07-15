export type NodeType =
    | 'start'
    | 'route'
    | 'middleware'
    | 'form_request'
    | 'authorization'
    | 'controller'
    | 'action'
    | 'service'
    | 'transaction'
    | 'condition'
    | 'model'
    | 'model_create'
    | 'model_update'
    | 'model_delete'
    | 'event'
    | 'job'
    | 'notification'
    | 'api_resource'
    | 'response'
    | 'success'
    | 'failure'
    | 'exception'
    | 'end';

export const IMPLEMENTED_NODE_TYPES: readonly NodeType[] = [
    'start',
    'route',
    'middleware',
    'form_request',
    'controller',
    'action',
    'transaction',
    'model_create',
    'model_update',
    'event',
    'job',
    'api_resource',
    'response',
    'condition',
    'end',
];

export interface Position {
    x: number;
    y: number;
}

interface BaseNodeData {
    laneId?: string | null;
    [key: string]: unknown;
}

export interface RouteNodeData extends BaseNodeData {
    method: string;
    uri: string;
    name?: string;
    domain?: string;
    middleware?: string[];
}

export interface MiddlewareNodeData extends BaseNodeData {
    middleware: string[];
}

export interface FormRequestNodeData extends BaseNodeData {
    class: string;
    namespace?: string;
    rules?: Record<string, string[]>;
}

export interface ControllerNodeData extends BaseNodeData {
    class: string;
    namespace?: string;
    method: string;
}

export interface ActionNodeData extends BaseNodeData {
    class: string;
    namespace?: string;
    method?: string;
    transactionEnabled?: boolean;
}

export interface TransactionNodeData extends BaseNodeData {
    label?: string;
}

export interface ModelCreateNodeData extends BaseNodeData {
    model: string;
}

export interface ModelUpdateNodeData extends BaseNodeData {
    model: string;
}

export interface EventNodeData extends BaseNodeData {
    class: string;
    generateIfMissing?: boolean;
}

export interface JobNodeData extends BaseNodeData {
    class: string;
    queue?: string;
    generateIfMissing?: boolean;
}

export interface ApiResourceNodeData extends BaseNodeData {
    class: string;
}

export interface ResponseNodeData extends BaseNodeData {
    responseType: string;
    status?: number;
    resourceClass?: string;
}

export interface ConditionNodeData extends BaseNodeData {
    label?: string;
}

export interface EndNodeData extends BaseNodeData {
    label?: string;
}

export interface GenericNodeData extends BaseNodeData {
    [key: string]: unknown;
}

export type ProcessNodeData =
    | RouteNodeData
    | MiddlewareNodeData
    | FormRequestNodeData
    | ControllerNodeData
    | ActionNodeData
    | TransactionNodeData
    | ModelCreateNodeData
    | ModelUpdateNodeData
    | EventNodeData
    | JobNodeData
    | ApiResourceNodeData
    | ResponseNodeData
    | ConditionNodeData
    | EndNodeData
    | GenericNodeData;

export interface ProcessNode {
    id: string;
    type: NodeType;
    position: Position;
    data: ProcessNodeData;
}

export type EdgeHandle = 'success' | 'failure' | 'exception' | 'input' | 'output' | null;

export interface ProcessEdge {
    id: string;
    source: string;
    sourceHandle?: string | null;
    target: string;
    targetHandle?: string | null;
    label?: string | null;
}

export type LaneActorType = 'human' | 'system' | null;

export interface ProcessLane {
    id: string;
    name: string;
    actorType: LaneActorType;
    order: number;
    color: string | null;
}

export type ProcessStatus = 'draft' | 'validated' | 'generated' | 'archived';

export interface ProcessMetadata {
    createdAt: string;
    updatedAt: string;
    generatedAt: string | null;
    generatorVersion: string | null;
}

export interface ProcessDefinition {
    schemaVersion: string;
    id: string;
    name: string;
    slug: string;
    guard?: string;
    description: string | null;
    version: number;
    status: ProcessStatus;
    entryNodeId: string | null;
    nodes: ProcessNode[];
    edges: ProcessEdge[];
    lanes: ProcessLane[];
    metadata: ProcessMetadata;
}

export interface ProcessSummaryListItem {
    id: string;
    name: string;
    slug: string;
    status: ProcessStatus;
    version: number;
}

export type ValidationSeverity = 'error' | 'warning';

export interface ValidationIssue {
    code: string;
    message: string;
    nodeId: string | null;
    field: string | null;
    severity: ValidationSeverity;
}

export interface ValidationResult {
    valid: boolean;
    errors: ValidationIssue[];
    warnings: ValidationIssue[];
}
