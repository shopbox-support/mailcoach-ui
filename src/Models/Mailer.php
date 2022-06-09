<?php

namespace Spatie\MailcoachUi\Models;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\MailcoachUi\Enums\MailerTransport;

class Mailer extends Model
{
    use HasFactory;

    public $table = 'mailcoach_mailers';

    public $guarded = [];

    public $casts = [
        'default' => 'boolean',
        'transport' => MailerTransport::class,
        'configuration' => AsEncryptedArrayObject::class,
        'ready_for_use' => 'boolean',
    ];

    public static function registerAllConfigValues()
    {
        Mailer::all()
            ->where('ready_for_use', true)
            ->each(function(Mailer $mailer) {

            });
    }

    public function registerConfigValues()
    {
        if (! $this->readyForUse()) {
            return;
        }

        if ($this->transport === MailerTransport::Ses) {
            config()->set("mail.mailers.{$this->configName()}", [
                'transport' => 'ses',
                'key' => $this->get('ses_key'),
                'secret' => $this->get('ses_secret'),
                'region' => $this->get('ses_region')
            ]);

            /*
            config()->set("services.{$this->configName()}", [
                'key' => $this->get('ses_key'),
                'secret' => $this->get('ses_secret'),
                'region' => $this->get('ses_region'),
            ]);
            */

            config()->set("mailcoach.{$this->configName()}.ses_feedback", [
                'configuration_set' => $this->get('ses_configuration_set') ?? '',
            ]);
        }

        if ($this->transport === MailerTransport::SendGrid) {
            config()->set("mail.mailers.{$this->configName()}", [
                'transport' => 'smtp',
                'host' => 'smtp.sendgrid.net',
                'username' => 'apikey',
                'password' => $this->get('api_key'),
                'encryption' => null,
                'port' => 587
            ]);

            config()->set('mailcoach.{$this->configName()}.signing_secret', [
                'signing_secret' => $this->get('signing_secret'),
            ]);
        }
    }

    public function configName(): string
    {
        return Str::kebab("mailcoach-{$this->name}");
    }

    public function isReadyForUse(): bool
    {
        return $this->ready_for_use;
    }

    public function get(string $configurationKey, ?string $default = null)
    {
        return Arr::get($this->configuration, $configurationKey) ?? $default;
    }

    public function merge(array $values): self
    {

        $newValues = array_merge($this->configuration?->toArray() ?? [], $values);

        $this->update(['configuration' => $newValues]);

        return $this;
    }

    public function markAsReadyForUse(): self
    {
        $this->update(['ready_for_use' => true]);

        return $this;
    }
}