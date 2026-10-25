<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\ServiceCategory;
use App\Models\User;

class InquiryResource extends Resource
{
    public static string $slug = 'demandes';

    public static string $model = Inquiry::class;

    public static string $area = 'inquiries';

    public static string $label = 'demande';

    public static string $labelPlural = 'Demandes';

    public static string $icon = 'inbox';

    public static string $group = 'Commercial';

    public static string $description = 'Contacts, devis, démonstrations et candidatures reçus via le site.';

    public static bool $canCreate = false;

    public static bool $canEdit = false;

    public static bool $canDelete = true;

    public static ?string $detailRoute = 'admin.inquiries.show';

    public static array $with = ['assignee', 'product', 'category'];

    public static array $searchable = ['reference', 'first_name', 'last_name', 'email', 'organization', 'subject'];

    public static array $defaultSort = ['created_at', 'desc'];

    public static array $exportable = ['reference', 'created_at', 'kind', 'status', 'first_name', 'last_name', 'organization', 'job_title', 'email', 'phone', 'country', 'subject', 'message', 'category', 'product', 'budget', 'timeline', 'assignee'];

    public static function columns(): array
    {
        return [new C('reference', 'Réf.', 'muted'), new C('full_name', 'Demandeur', 'title'), new C('organization', 'Organisation'), new C('kind', 'Type'),
            new C('status', 'Statut', 'badge'), new C('assignee', 'Responsable'), new C('created_at', 'Reçue', 'datetime', 'created_at')];
    }

    public static function filters(): array
    {
        return [Filters::enum('kind', 'Type', InquiryKind::class), Filters::enum('status', 'Statut', InquiryStatus::class),
            Filters::model('assigned_to_id', 'Responsable', User::class, 'first_name'), Filters::model('service_category_id', 'Domaine', ServiceCategory::class)];
    }
}
