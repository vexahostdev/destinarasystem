<?php

namespace App\Enums;

enum DestinationStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case REVISION_REQUIRED = 'revision_required';
    case PUBLISHED = 'published';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::PENDING_REVIEW => 'Menunggu Kurasi',
            self::REVISION_REQUIRED => 'Perlu Revisi',
            self::PUBLISHED => 'Tayang',
            self::INACTIVE => 'Nonaktif',
            self::ARCHIVED => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'amber',
            self::REVISION_REQUIRED => 'rose',
            self::PUBLISHED => 'emerald',
            self::INACTIVE => 'zinc',
            self::ARCHIVED => 'slate',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-700 border border-gray-200',
            self::PENDING_REVIEW => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::REVISION_REQUIRED => 'bg-rose-50 text-rose-700 border border-rose-200',
            self::PUBLISHED => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::INACTIVE => 'bg-zinc-100 text-zinc-700 border border-zinc-200',
            self::ARCHIVED => 'bg-slate-100 text-slate-700 border border-slate-200',
        };
    }
}
