import re
path = 'resources/views/complaints/create.blade.php'
with open(path, 'r') as f:
    content = f.read()

# 1. Update draft banner HTML to include Start over button
old_banner = '''            <div class="wizard-draft-banner" id="wizard-draft-banner" hidden>
                <span>Resume your previous draft?</span>
                <div>
                    <button type="button" id="wizard-draft-resume">Resume</button>
                </div>
            </div>'''

new_banner = '''            <div class="wizard-draft-banner" id="wizard-draft-banner" hidden>
                <span>Resume your previous draft?</span>
                <div class="wizard-draft-actions">
                    <button type="button" id="wizard-draft-resume">Resume</button>
                    <button type="button" id="wizard-draft-reset" class="wizard-draft-reset">Start over</button>
                </div>
            </div>'''

if old_banner in content:
    content = content.replace(old_banner, new_banner)

# 2. Update CSS for mobile hero, aside, action bar
old_media = '''        @media (max-width: 640px) {
            .filing-stepper {
                display: none !important;
            }'''

new_media = '''        @media (max-width: 640px) {
            .filing-stepper {
                display: none !important;
            }
            .filing-hero {
                padding: 16px !important;
                margin-bottom: 12px !important;
            }
            .filing-hero h1 {
                font-size: 24px !important;
                line-height: 1.2 !important;
            }
            .filing-hero__subtitle,
            .filing-hero__card {
                display: none !important;
            }
            .filing-aside {
                display: none !important;
            }'''

if old_media in content:
    content = content.replace(old_media, new_media)

# 3. Update JavaScript logic for touchedNext, draft non-empty count >= 2, and start over
old_js_state = '''                let currentStep = 1;
                let touchedBack = {};'''

new_js_state = '''                let currentStep = 1;
                let touchedBack = {};
                let touchedNext = {};'''

if old_js_state in content:
    content = content.replace(old_js_state, new_js_state)

old_validate_ui = '''                    // Validate current step
                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (btnNext && currentStep < totalSteps) {
                            btnNext.disabled = !res.valid;
                        }
                        if (stepHint) {
                            if (!res.valid && res.hint) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            } else {
                                stepHint.hidden = true;
                            }
                        }
                    }'''

new_validate_ui = '''                    // Validate current step
                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (btnNext && currentStep < totalSteps) {
                            btnNext.disabled = !res.valid;
                        }
                        if (stepHint) {
                            if (!res.valid && res.hint && touchedNext[currentStep]) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            } else {
                                stepHint.hidden = true;
                            }
                        }
                    }'''

if old_validate_ui in content:
    content = content.replace(old_validate_ui, new_validate_ui)

old_btn_next_ev = '''                btnNext?.addEventListener('click', (e) => {
                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (!res.valid) {
                            e.preventDefault();
                            if (stepHint) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            }
                            return;
                        }
                    }
                    if (currentStep < totalSteps) {
                        goToStep(currentStep + 1);
                    }
                });'''

new_btn_next_ev = '''                btnNext?.addEventListener('click', (e) => {
                    touchedNext[currentStep] = true;
                    const config = stepConfigs[currentStep];
                    if (config) {
                        const res = config.validate();
                        if (!res.valid) {
                            e.preventDefault();
                            if (stepHint) {
                                stepHint.textContent = res.hint;
                                stepHint.hidden = false;
                            }
                            return;
                        }
                    }
                    if (currentStep < totalSteps) {
                        goToStep(currentStep + 1);
                    }
                });'''

if old_btn_next_ev in content:
    content = content.replace(old_btn_next_ev, new_btn_next_ev)

old_draft_check = '''                // Draft banner check
                const existingDraft = loadDraft();
                @if(!$errors->any())
                if (existingDraft && draftBanner) {
                    draftBanner.hidden = false;
                }
                @endif

                draftResumeBtn?.addEventListener('click', () => {
                    applyDraft(existingDraft);
                    if (draftBanner) draftBanner.hidden = true;
                });'''

new_draft_check = '''                // Draft banner check
                const existingDraft = loadDraft();
                @if(!$errors->any())
                if (existingDraft && draftBanner) {
                    const nonEmpCount = Object.values(existingDraft).filter(v => v !== '' && v !== false && v !== null && v !== undefined).length;
                    if (nonEmpCount >= 2) {
                        draftBanner.hidden = false;
                    }
                }
                @endif

                draftResumeBtn?.addEventListener('click', () => {
                    applyDraft(existingDraft);
                    if (draftBanner) draftBanner.hidden = true;
                });

                const draftResetBtn = document.getElementById('wizard-draft-reset');
                draftResetBtn?.addEventListener('click', () => {
                    try {
                        sessionStorage.removeItem(draftKey);
                    } catch (e) {}
                    if (draftBanner) draftBanner.hidden = true;
                });'''

if old_draft_check in content:
    content = content.replace(old_draft_check, new_draft_check)

with open(path, 'w') as f:
    f.write(content)

print('Successfully applied fixes to create.blade.php')