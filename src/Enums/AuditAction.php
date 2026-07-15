<?php

declare(strict_types=1);

namespace MohamedZaki\LaravelProcessBuilder\Enums;

enum AuditAction: string
{
    case ProcessCreated = 'process_created';
    case ProcessUpdated = 'process_updated';
    case ProcessDeleted = 'process_deleted';
    case ProcessValidated = 'process_validated';
    case PreviewCreated = 'preview_created';
    case GenerationStarted = 'generation_started';
    case GenerationCompleted = 'generation_completed';
    case GenerationFailed = 'generation_failed';
    case BackupCreated = 'backup_created';
    case RollbackStarted = 'rollback_started';
    case RollbackCompleted = 'rollback_completed';
    case RollbackFailed = 'rollback_failed';
}
