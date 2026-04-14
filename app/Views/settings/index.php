<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Tenant settings</h2>
            <p class="module-subtitle">Manage institute profile defaults, operational visibility, and communication integrations.</p>
        </div>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('settings/profile') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Institute profile</h3>
                    <p class="module-subtitle">Core tenant identity and global defaults for the institute.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Institute name</span>
                    <input type="text" name="name" value="<?= esc(old('name', $tenant->name ?? '')) ?>" required>
                </label>
                <label class="field">
                    <span>Slug</span>
                    <input type="text" value="<?= esc($tenant->slug ?? '') ?>" readonly>
                </label>
                <label class="field">
                    <span>Legal name</span>
                    <input type="text" name="legal_name" value="<?= esc(old('legal_name', $tenant->legal_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Owner name</span>
                    <input type="text" name="owner_name" value="<?= esc(old('owner_name', $tenant->owner_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Owner email</span>
                    <input type="email" name="owner_email" value="<?= esc(old('owner_email', $tenant->owner_email ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Owner phone</span>
                    <input type="text" name="owner_phone" value="<?= esc(old('owner_phone', $tenant->owner_phone ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Default timezone</span>
                    <input type="text" name="default_timezone" value="<?= esc(old('default_timezone', $tenant->default_timezone ?? 'UTC')) ?>">
                </label>
                <label class="field">
                    <span>Default currency code</span>
                    <input type="text" name="default_currency_code" value="<?= esc(old('default_currency_code', $tenant->default_currency_code ?? 'USD')) ?>">
                </label>
                <label class="field">
                    <span>Country code</span>
                    <input type="text" name="country_code" value="<?= esc(old('country_code', $tenant->country_code ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Locale code</span>
                    <input type="text" name="locale_code" value="<?= esc(old('locale_code', $tenant->locale_code ?? 'en')) ?>">
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Save profile</button>
            </div>
        </form>

        <form class="form-card" method="post" action="<?= site_url('settings/preferences') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Branding and visibility</h3>
                    <p class="module-subtitle">Control tenant-facing branding defaults and branch-level visibility behavior.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Branding name</span>
                    <input type="text" name="branding_name" value="<?= esc(old('branding_name', $settings->branding_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Default timezone</span>
                    <input type="text" name="default_timezone" value="<?= esc(old('default_timezone', $settings->default_timezone ?? ($tenant->default_timezone ?? 'UTC'))) ?>">
                </label>
                <label class="field">
                    <span>Default currency code</span>
                    <input type="text" name="default_currency_code" value="<?= esc(old('default_currency_code', $settings->default_currency_code ?? ($tenant->default_currency_code ?? 'USD'))) ?>">
                </label>
                <label class="field">
                    <span>Locale code</span>
                    <input type="text" name="locale_code" value="<?= esc(old('locale_code', $settings->locale_code ?? ($tenant->locale_code ?? 'en'))) ?>">
                </label>
                <label class="field">
                    <span>Branch visibility mode</span>
                    <select name="branch_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('branch_visibility_mode', $settings->branch_visibility_mode ?? 'restricted') === $mode ? 'selected' : '' ?>>
                                <?= esc(ucfirst($mode)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Enquiry visibility mode</span>
                    <select name="enquiry_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('enquiry_visibility_mode', $settings->enquiry_visibility_mode ?? 'restricted') === $mode ? 'selected' : '' ?>>
                                <?= esc(ucfirst($mode)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Admission visibility mode</span>
                    <select name="admission_visibility_mode">
                        <?php foreach (['own', 'restricted', 'all'] as $mode): ?>
                            <option value="<?= esc($mode) ?>" <?= old('admission_visibility_mode', $settings->admission_visibility_mode ?? 'restricted') === $mode ? 'selected' : '' ?>>
                                <?= esc(ucfirst($mode)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Save preferences</button>
            </div>
        </form>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('settings/email') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">SMTP configuration</h3>
                    <p class="module-subtitle">Tenant-level email delivery defaults for notifications and system mail.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Provider name</span>
                    <input type="text" name="provider_name" value="<?= esc(old('provider_name', $emailConfig->provider_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>From name</span>
                    <input type="text" name="from_name" value="<?= esc(old('from_name', $emailConfig->from_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>From email</span>
                    <input type="email" name="from_email" value="<?= esc(old('from_email', $emailConfig->from_email ?? '')) ?>">
                </label>
                <label class="field">
                    <span>SMTP host</span>
                    <input type="text" name="host" value="<?= esc(old('host', $emailConfig->host ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Port</span>
                    <input type="number" name="port" value="<?= esc(old('port', $emailConfig->port ?? '587')) ?>">
                </label>
                <label class="field">
                    <span>Encryption</span>
                    <input type="text" name="encryption" value="<?= esc(old('encryption', $emailConfig->encryption ?? 'tls')) ?>">
                </label>
                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" value="<?= esc(old('username', $emailConfig->username ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" value="<?= esc(old('password')) ?>" placeholder="<?= ! empty($emailConfig?->has_password) ? 'Stored - leave blank to keep existing password' : '' ?>">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= old('status', $emailConfig->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $emailConfig->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Save SMTP</button>
            </div>
        </form>

        <form class="form-card" method="post" action="<?= site_url('settings/whatsapp') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">WhatsApp configuration</h3>
                    <p class="module-subtitle">Tenant-level WhatsApp sender defaults for campaign and transactional communication.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Provider name</span>
                    <input type="text" name="provider_name" value="<?= esc(old('provider_name', $whatsappConfig->provider_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>API base URL</span>
                    <input type="text" name="api_base_url" value="<?= esc(old('api_base_url', $whatsappConfig->api_base_url ?? '')) ?>">
                </label>
                <label class="field">
                    <span>API key</span>
                    <input type="password" name="api_key" value="<?= esc(old('api_key')) ?>" placeholder="<?= ! empty($whatsappConfig?->has_api_key) ? 'Stored - leave blank to keep existing API key' : '' ?>">
                </label>
                <label class="field">
                    <span>Sender ID</span>
                    <input type="text" name="sender_id" value="<?= esc(old('sender_id', $whatsappConfig->sender_id ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= old('status', $whatsappConfig->status ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $whatsappConfig->status ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Save WhatsApp</button>
            </div>
        </form>
    </div>
</section>
<?= $this->endSection() ?>

