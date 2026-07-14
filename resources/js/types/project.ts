export interface RouteInfo {
    methods: string[];
    uri: string;
    name: string | null;
    domain: string | null;
    action: string | null;
    controller: string | null;
    controllerMethod: string | null;
    middleware: string[];
    parameters: string[];
    isVendorRoute: boolean;
    isPackageInternal: boolean;
}

export interface ParameterInfo {
    name: string;
    type: string | null;
    isOptional: boolean;
    hasDefault: boolean;
    isVariadic: boolean;
}

export interface MethodInfo {
    name: string;
    exists: boolean;
    isPublic: boolean;
    parameters: ParameterInfo[];
    returnType: string | null;
    formRequestParameter: string | null;
}

export interface ControllerInfo {
    class: string;
    exists: boolean;
    filePath: string | null;
    isInvokable: boolean;
    methods: MethodInfo[];
    constructorDependencies: ParameterInfo[];
}

export interface ProjectSummary {
    routes: RouteInfo[];
    routeCount: number;
    controllerCount: number;
    namedRouteCount: number;
    unnamedRouteCount: number;
    routesByMethod: Record<string, number>;
    duplicateRouteNames: string[];
    routesWithMissingControllers: RouteInfo[];
}

export interface ApiEnvelope<T> {
    data: T;
    meta: Record<string, unknown>;
    errors: unknown[];
}
