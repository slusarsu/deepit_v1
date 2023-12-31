<?php

namespace App\Filament\Pages;

use AllowDynamicProperties;
use Creagia\FilamentCodeField\CodeField;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\ActionGroup;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Spatie\Valuestore\Valuestore;

#[AllowDynamicProperties] class Settings extends Page
{
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.settings';

    protected static ?int $navigationSort = 4;

    protected Valuestore $valueStore;

    protected array $parameters;

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->valueStore = siteSetting();

        $this->parameters = config('adm.setting_parameters');
    }

    public function mount(): void
    {
        $this->form->fill($this->prepareParameters());
    }

    protected function prepareParameters(): array
    {
        $parameters = [];

        foreach ($this->parameters as $parameter => $value) {
            $parameters[$parameter] = $this->valueStore->get($parameter);
        }

        return $parameters;
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Heading')
                ->tabs([
                    Tab::make('Site settings')
                        ->schema([
                            TextInput::make('name'),

                            FileUpload::make('logo')
                                ->directory('logo')
                                ->image(),

                            FileUpload::make('footerLogo')
                                ->directory('logo')
                                ->image(),

                            Toggle::make('isTextLogo')
                                ->default(true),

                            TextInput::make('email')->email(true),

                            TextInput::make('copyright'),

                            Toggle::make('isEnabled')
                                ->default(true),
                        ]),
                    Tab::make('SEO')
                        ->schema([
                            TextInput::make('author'),
                            TextInput::make('seoTitle'),
                            TextInput::make('seoKeyWords'),
                            Textarea::make('seoDescription'),
                            CodeField::make('googleTagManager')
                                ->htmlField()
                                ->withLineNumbers(),
                            CodeField::make('metaPixelCode')
                                ->htmlField()
                                ->withLineNumbers(),
                        ]),
                    Tab::make('Content')
                        ->schema([
                            TextInput::make('paginationCount')
                                ->integer(true)
                                ->default(9),

                            Toggle::make('showRandomImages')
                                ->default(true),
                        ]),
                    Tab::make('Customization')
                        ->schema([
                            CodeField::make('customHeaderCode')
                                ->htmlField()
                                ->withLineNumbers(),
                            CodeField::make('customFooterCode')
                                ->htmlField()
                                ->withLineNumbers(),
                        ]),
                    Tab::make('Custom Style')
                        ->schema([
                            CodeField::make('customCss')
                                ->cssField()
                                ->withLineNumbers(),
                        ]),
                ]),
        ];

    }

    public function submit(): void
    {
        $result = $this->form->getState();

        foreach ($result as $field => $value) {
            $this->valueStore->put($field, $value);
        }

        Artisan::call('optimize:clear');

        Notification::make()
            ->title('Saved successfully')
            ->icon('heroicon-o-sparkles')
            ->iconColor('success')
            ->send();
    }

    protected function getActions(): array
    {
        return [

            Action::make('Clear Cache')
                ->action(function () {
                    Artisan::call('optimize:clear');

                    Notification::make()
                        ->title('Fixed!')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->color('success'),

            ActionGroup::make([
                Action::make('Add Demo')
                    ->action(function () {
                        Artisan::call('adm:demo');

                        Notification::make()
                            ->title('Added demo data')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('danger'),

                Action::make('Remove Demo')
                    ->action(function () {
                        Artisan::call('adm:demo-remove');

                        Notification::make()
                            ->title('Removed demo data')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('danger'),

                Action::make('Reinstall site (clear all data!!)')
                    ->action(function () {
                        Artisan::call('adm:restart');

                        Notification::make()
                            ->title('Removed all demo')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('danger'),
            ])->label('Demo Data')->color('danger'),
        ];
    }

}
