<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="module-page">
    <?php
    $settingsTabs = [
        'company' => 'Company',
        'defaults' => 'Defaults',
        'enquiries' => 'Enquiries',
        'communication' => 'Communication',
    ];
    $regionalInputOptions = $regionalInputOptions ?? [];
    $timezoneOptions = $regionalInputOptions['timezones'] ?? [];
    $currencyOptions = $regionalInputOptions['currencies'] ?? [];
    $localeOptions = $regionalInputOptions['locales'] ?? [];
    $countryOptions = $regionalInputOptions['countries'] ?? [];
    ?>

    <div class="module-toolbar">
        <div>
            <h2 class="module-title">Company Settings</h2>
            <p class="module-subtitle">Keep company details, defaults, enquiry rules, and communication setup in one place.</p>
        </div>
    </div>

    <div class="settings-tabs" data-settings-tabs>
        <div class="settings-tabs__nav" role="tablist" aria-label="Settings sections">
            <?php foreach ($settingsTabs as $tabKey => $tabLabel): ?>
                <button
                    class="settings-tabs__button"
                    type="button"
                    data-tab-trigger="<?= esc($tabKey) ?>"
                    role="tab"
                    aria-selected="false"
                    aria-controls="settings-tab-<?= esc($tabKey) ?>"
                    id="settings-tab-button-<?= esc($tabKey) ?>"
                >
                    <?= esc($tabLabel) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <section
            class="settings-tabs__panel"
            data-tab-panel="company"
            id="settings-tab-company"
            role="tabpanel"
            aria-labelledby="settings-tab-button-company"
        >
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
                            <select name="default_timezone">
                                <?php $selectedTimezone = old('default_timezone', $tenant->default_timezone ?? 'UTC'); ?>
                                <?php foreach ($timezoneOptions as $timezone): ?>
                                    <option value="<?= esc($timezone) ?>" <?= $selectedTimezone === $timezone ? 'selected' : '' ?>>
                                        <?= esc($timezone) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Currency</span>
                            <select name="default_currency_code">
                                <?php $selectedCurrency = old('default_currency_code', $tenant->default_currency_code ?? 'USD'); ?>
                                <?php foreach ($currencyOptions as $code => $label): ?>
                                    <option value="<?= esc($code) ?>" <?= $selectedCurrency === $code ? 'selected' : '' ?>>
                                        <?= esc($code . ' - ' . $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Country</span>
                            <select name="country_code">
                                <?php $selectedCountry = old('country_code', $tenant->country_code ?? ''); ?>
                                <?php foreach ($countryOptions as $code => $label): ?>
                                    <option value="<?= esc($code) ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Language locale</span>
                            <select name="locale_code">
                                <?php $selectedLocale = old('locale_code', $tenant->locale_code ?? 'en'); ?>
                                <?php foreach ($localeOptions as $code => $label): ?>
                                    <option value="<?= esc($code) ?>" <?= $selectedLocale === $code ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                            <p class="module-subtitle">Name shown inside the app for your team.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span>Brand name</span>
                            <input type="text" name="branding_name" value="<?= esc(old('branding_name', $settings->branding_name ?? '')) ?>">
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="shell-button shell-button--primary" type="submit">Save branding</button>
                    </div>
                </form>
            </div>
        </section>

        <section
            class="settings-tabs__panel"
            data-tab-panel="defaults"
            id="settings-tab-defaults"
            role="tabpanel"
            aria-labelledby="settings-tab-button-defaults"
        >
            <div class="settings-grid">
                <?= view('settings/_catalog_sections', ['sections' => $catalogSections]) ?>
            </div>
        </section>

        <section
            class="settings-tabs__panel"
            data-tab-panel="enquiries"
            id="settings-tab-enquiries"
            role="tabpanel"
            aria-labelledby="settings-tab-button-enquiries"
        >
            <div class="settings-grid">
                <?= view('settings/_catalog_sections', ['sections' => $enquiryCatalogSections ?? []]) ?>
            </div>
        </section>

        <section
            class="settings-tabs__panel"
            data-tab-panel="communication"
            id="settings-tab-communication"
            role="tabpanel"
            aria-labelledby="settings-tab-button-communication"
        >
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
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-settings-tabs]');
            if (!root) {
                return;
            }

            const triggers = Array.from(root.querySelectorAll('[data-tab-trigger]'));
            const panels = Array.from(root.querySelectorAll('[data-tab-panel]'));
            const storageKey = 'edcrm-settings-active-tab';

            const activate = (key) => {
                triggers.forEach((trigger) => {
                    const isActive = trigger.dataset.tabTrigger === key;
                    trigger.classList.toggle('settings-tabs__button--active', isActive);
                    trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const isActive = panel.dataset.tabPanel === key;
                    panel.hidden = !isActive;
                });

                window.localStorage.setItem(storageKey, key);
            };

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', () => activate(trigger.dataset.tabTrigger));
            });

            const initial = window.localStorage.getItem(storageKey) || triggers[0]?.dataset.tabTrigger;
            if (initial) {
                activate(initial);
            }
        })();
    </script>
</section>
<?= $this->endSection() ?>
