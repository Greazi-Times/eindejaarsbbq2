<?php

namespace App\Filament\Resources\Enrollments;

use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentInfolist;
use App\Filament\Resources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EnrollmentResource extends Resource
{
    public const VIEW_PERSONAL_DATA_PERMISSION = 'ViewEnrollmentPersonalData';

    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencil;

    protected static string|\UnitEnum|null $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return EnrollmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EnrollmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
    }

    public static function canViewPersonalData(): bool
    {
        return Auth::user()?->can(self::VIEW_PERSONAL_DATA_PERMISSION) ?? false;
    }

    public static function formatFullNamePreview(?string $name): string
    {
        return static::obfuscateName($name);
    }

    public static function formatEmailPreview(?string $email): string
    {
        return static::obfuscateEmail($email);
    }

    private static function obfuscateName(?string $name): string
    {
        if (blank($name)) {
            return '-';
        }

        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->map(function (string $part): string {
                $firstCharacter = Str::substr($part, 0, 1);
                $maskedLength = max(Str::length($part) - 1, 2);

                return $firstCharacter.str_repeat('*', $maskedLength);
            })
            ->implode(' ');
    }

    private static function obfuscateEmail(?string $email): string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return '-';
        }

        [$localPart, $domain] = explode('@', $email, 2);

        $maskedLocalPart = Str::substr($localPart, 0, 1).str_repeat('*', max(Str::length($localPart) - 1, 2));

        return "{$maskedLocalPart}@{$domain}";
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnrollments::route('/'),
            'create' => CreateEnrollment::route('/create'),
            'view' => ViewEnrollment::route('/{record}'),
            'edit' => EditEnrollment::route('/{record}/edit'),
        ];
    }
}
