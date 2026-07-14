import { useEffect } from 'react';
import { useProjectStore } from '@/stores/useProjectStore';

export function ProjectExplorer(): JSX.Element {
    const { summary, isLoading, error, scan } = useProjectStore();

    useEffect(() => {
        void scan();
    }, [scan]);

    return (
        <section className="pb-explorer">
            <header className="pb-explorer-header">
                <h2>Project Explorer</h2>
                <button type="button" onClick={() => void scan()} disabled={isLoading}>
                    {isLoading ? 'Scanning…' : 'Rescan'}
                </button>
            </header>

            {error && <p role="alert">{error}</p>}

            {summary && (
                <>
                    <dl className="pb-explorer-stats">
                        <div>
                            <dt>Routes</dt>
                            <dd>{summary.routeCount}</dd>
                        </div>
                        <div>
                            <dt>Controllers</dt>
                            <dd>{summary.controllerCount}</dd>
                        </div>
                        <div>
                            <dt>Named</dt>
                            <dd>{summary.namedRouteCount}</dd>
                        </div>
                        <div>
                            <dt>Unnamed</dt>
                            <dd>{summary.unnamedRouteCount}</dd>
                        </div>
                    </dl>

                    {summary.duplicateRouteNames.length > 0 && (
                        <p role="alert">
                            Duplicate route names: {summary.duplicateRouteNames.join(', ')}
                        </p>
                    )}

                    {summary.routesWithMissingControllers.length > 0 && (
                        <p role="alert">
                            {summary.routesWithMissingControllers.length} route(s) reference a missing controller.
                        </p>
                    )}

                    <table className="pb-explorer-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>URI</th>
                                <th>Name</th>
                                <th>Controller</th>
                                <th>Middleware</th>
                            </tr>
                        </thead>
                        <tbody>
                            {summary.routes.map((route) => (
                                <tr key={`${route.methods.join(',')}-${route.uri}`}>
                                    <td>{route.methods.join('|')}</td>
                                    <td>{route.uri}</td>
                                    <td>{route.name ?? '—'}</td>
                                    <td>
                                        {route.controller
                                            ? `${route.controller}@${route.controllerMethod ?? ''}`
                                            : 'Closure'}
                                    </td>
                                    <td>{route.middleware.join(', ')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </>
            )}
        </section>
    );
}
