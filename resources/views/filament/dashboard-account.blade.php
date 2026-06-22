@php
    $user = filament()->auth()->user();
@endphp

<div class="dashboard-header-account" data-dashboard-header-account>
    <x-filament-panels::avatar.user
        size="md"
        :user="$user"
        loading="lazy"
    />

    <div class="dashboard-header-account__main">
        <span class="dashboard-header-account__welcome">
            {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
        </span>

        <span class="dashboard-header-account__name">
            {{ filament()->getUserName($user) }}
        </span>
    </div>

    <form
        action="{{ filament()->getLogoutUrl() }}"
        method="post"
        class="dashboard-header-account__logout"
    >
        @csrf

        <x-filament::button
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle"
            :icon-alias="\Filament\View\PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON"
            labeled-from="sm"
            size="sm"
            tag="button"
            type="submit"
        >
            {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
        </x-filament::button>
    </form>
</div>
