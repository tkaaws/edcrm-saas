<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Company Settings</h2>
            <p class="module-subtitle">Manage workspace details, company information, and communication settings.</p>
        </div>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('settings/profile') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                        <h3 class="module-title module-title--small">Company profile</h3>
                        <p class="module-subtitle">Core workspace details and daily operating defaults.</p>
                    </div>
                </div>

            <div class="form-grid">
                <label class="field">
                    <span>Company name</span>
                    <input type="text" name="name" value="<?= esc(old('name', $tenant->name ?? '')) ?>" required>
                </label>
                <label class="field">
                    <span>Company code</span>
                    <input type="text" value="<?= esc($tenant->slug ?? '') ?>" readonly>
                </label>
                <label class="field">
                    <span>Legal name</span>
                    <input type="text" name="legal_name" value="<?= esc(old('legal_name', $tenant->legal_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Primary contact name</span>
                    <input type="text" name="owner_name" value="<?= esc(old('owner_name', $tenant->owner_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Primary contact email</span>
                    <input type="email" name="owner_email" value="<?= esc(old('owner_email', $tenant->owner_email ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Primary contact phone</span>
                    <input type="text" name="owner_phone" value="<?= esc(old('owner_phone', $tenant->owner_phone ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Timezone</span>
                    <input type="text" name="default_timezone" value="<?= esc(old('default_timezone', $tenant->default_timezone ?? 'UTC')) ?>">
                </label>
                <label class="field">
                    <span>Currency</span>
                    <input type="text" name="default_currency_code" value="<?= esc(old('default_currency_code', $tenant->default_currency_code ?? 'USD')) ?>">
                </label>
                <label class="field">
                    <span>Country</span>
                    <input type="text" name="country_code" value="<?= esc(old('country_code', $tenant->country_code ?? '')) ?>">
                </label>
                <label class="field">
                    <span>Language locale</span>
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
                    <h3 class="module-title module-title--small">Branding</h3>
                    <p class="module-subtitle">Name and branding text shown inside the app.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Brand name</span>
                    <input type="text" name="branding_name" value="<?= esc(old('branding_name', $settings->branding_name ?? '')) ?>">
                </label>
            </div>

            <div class="form-actions">
                <button class="shell-button shell-button--primary" type="submit">Save preferences</button>
            </div>
        </form>

        <section class="form-card">
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Enquiry settings</h3>
                    <p class="module-subtitle">Control who can see enquiries, how duplicates are checked, how owners are assigned, and when inactive enquiries close.</p>
                </div>
            </div>

            <div class="form-actions">
                <a class="shell-button shell-button--primary" href="<?= esc($enquirySettingsUrl ?? site_url('settings/enquiry')) ?>">Open enquiry settings</a>
            </div>
        </section>
    </div>

    <div class="settings-grid">
        <?php foreach ($catalogSections as $section): ?>
            <form class="form-card" method="post" action="<?= site_url('settings/catalog/' . $section['category']) ?>">
                <?= csrf_field() ?>
                <div class="module-toolbar">
                    <div>
                        <h3 class="module-title module-title--small"><?= esc($section['title']) ?></h3>
                        <p class="module-subtitle"><?= esc($section['subtitle']) ?></p>
                    </div>
                </div>

                <div class="form-grid">
                    <?php foreach ($section['fields'] as $field): ?>
                        <?php
                        $definition = $field['definition'];
                        $formKey = $field['formKey'];
                        $value = old($formKey, $field['value']);
                        $options = $field['options'];
                        $optionLabels = $field['optionLabels'] ?? [];
                        $isLocked = $field['isLocked'];
                        $lockMode = $field['lockMode'];
                        $valueType = $definition->value_type;
                        ?>
                        <label class="field<?= $valueType === 'json' ? ' field--full' : '' ?>">
                            <span><?= esc($definition->label) ?></span>

                            <?php if ($valueType === 'bool' || $valueType === 'boolean'): ?>
                                <input type="hidden" name="<?= esc($formKey) ?>" value="0">
                                <label class="checkbox-row">
                                    <input type="checkbox" name="<?= esc($formKey) ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $isLocked ? 'disabled' : '' ?>>
                                    <span><?= esc($definition->description ?: 'Enabled') ?></span>
                                </label>
                            <?php elseif ($options !== []): ?>
                                <select name="<?= esc($formKey) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?= esc($option) ?>" <?= (string) $value === $option ? 'selected' : '' ?>>
                                            <?= esc($optionLabels[$option] ?? ucwords(str_replace(['_', '-'], ' ', $option))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($valueType === 'int' || $valueType === 'integer'): ?>
                                <input type="number" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                            <?php elseif ($valueType === 'json' || $valueType === 'array' || $valueType === 'object'): ?>
                                <textarea name="<?= esc($formKey) ?>" rows="3" <?= $isLocked ? 'disabled' : '' ?>><?= esc(is_array($value) ? implode(', ', $value) : (string) $value) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?= esc($formKey) ?>" value="<?= esc((string) $value) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                            <?php endif; ?>

                            <?php if ($definition->description): ?>
                                <small><?= esc($definition->description) ?></small>
                            <?php endif; ?>

                            <?php if ($isLocked): ?>
                                <small>This setting is currently locked by platform policy (<?= esc($lockMode) ?>).</small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button class="shell-button shell-button--primary" type="submit">Save <?= esc($section['title']) ?></button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="settings-grid">
        <form class="form-card" method="post" action="<?= site_url('settings/email') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">Email setup</h3>
                    <p class="module-subtitle">How this account sends notifications and other emails.</p>
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
                    <span>Email server</span>
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
                        <button class="shell-button shell-button--primary" type="submit">Save email setup</button>
                    </div>
                </form>

        <form class="form-card" method="post" action="<?= site_url('settings/whatsapp') ?>">
            <?= csrf_field() ?>
            <div class="module-toolbar">
                <div>
                    <h3 class="module-title module-title--small">WhatsApp setup</h3>
                    <p class="module-subtitle">How WhatsApp notifications are sent from this workspace.</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Provider name</span>
                    <input type="text" name="provider_name" value="<?= esc(old('provider_name', $whatsappConfig->provider_name ?? '')) ?>">
                </label>
                <label class="field">
                    <span>API endpoint</span>
                    <input type="text" name="api_base_url" value="<?= esc(old('api_base_url', $whatsappConfig->api_base_url ?? '')) ?>">
                </label>
                <label class="field">
                    <span>API token</span>
                    <input type="password" name="api_key" value="<?= esc(old('api_key')) ?>" placeholder="<?= ! empty($whatsappConfig?->has_api_key) ? 'Stored - leave blank to keep existing API key' : '' ?>">
                </label>
                <label class="field">
                    <span>Sender number</span>
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
                    <button class="shell-button shell-button--primary" type="submit">Save WhatsApp setup</button>
                </div>
            </form>
        </div>
</section>
<?= $this->endSection() ?>

