import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { ValidationIssue } from '@/types/process';

function IssueRow({ issue }: { issue: ValidationIssue }): JSX.Element {
    return (
        <li className={`pb-validation-issue pb-validation-issue--${issue.severity}`}>
            <span className="pb-validation-code">{issue.code}</span>
            <span className="pb-validation-message">{issue.message}</span>
        </li>
    );
}

export function ValidationPanel(): JSX.Element | null {
    const validation = useProcessEditorStore((state) => state.validation);

    if (validation === null) {
        return null;
    }

    return (
        <section className="pb-validation-panel" aria-label="Validation results">
            <h3>{validation.valid ? 'Process is valid' : 'Validation errors'}</h3>

            {validation.errors.length > 0 && (
                <ul>
                    {validation.errors.map((issue, index) => (
                        <IssueRow key={`${issue.code}-${index}`} issue={issue} />
                    ))}
                </ul>
            )}

            {validation.warnings.length > 0 && (
                <ul>
                    {validation.warnings.map((issue, index) => (
                        <IssueRow key={`${issue.code}-${index}`} issue={issue} />
                    ))}
                </ul>
            )}
        </section>
    );
}
