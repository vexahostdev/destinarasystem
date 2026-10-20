<?php

namespace App\Enums;

enum InquiryType: string
{
    case STUDY_TOUR = 'study_tour';
    case PENELITIAN = 'penelitian';

    public function label(): string
    {
        return match ($this) {
            self::STUDY_TOUR => 'Study Tour / Rombongan Sekolah',
            self::PENELITIAN => 'Penelitian / Akademik',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STUDY_TOUR => 'emerald',
            self::PENELITIAN => 'indigo',
        };
    }
}
