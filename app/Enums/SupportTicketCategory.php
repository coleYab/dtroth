<?php

namespace App\Enums;

enum SupportTicketCategory: string
{
    case Billing = 'billing';
    case TechnicalIssue = 'technical_issue';
    case Account = 'account';
    case FeatureRequest = 'feature_request';
    case Other = 'other';

    /**
     * Human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Billing => 'Billing',
            self::TechnicalIssue => 'Technical Issue',
            self::Account => 'Account',
            self::FeatureRequest => 'Feature Request',
            self::Other => 'Other',
        };
    }
}
