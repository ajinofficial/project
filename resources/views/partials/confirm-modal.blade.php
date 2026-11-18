<div class="confirm-modal" data-confirm-modal hidden>
    <div class="confirm-modal__backdrop" data-confirm-cancel></div>
    <section class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">
        <div class="confirm-modal__icon" aria-hidden="true">!</div>
        <div class="confirm-modal__body">
            <h2 id="confirm-modal-title" data-confirm-title>{{ __('common.confirm_action_50649c') }}</h2>
            <p id="confirm-modal-message" data-confirm-message>{{ __('common.are_you_sure_you_want_to_continue_8de6e7') }}</p>
        </div>
        <div class="confirm-modal__actions">
            <button class="ghost-button" type="button" data-confirm-cancel>{{ __('common.cancel_77dfd2') }}</button>
            <button class="danger-button" type="button" data-confirm-submit>{{ __('common.confirm_04a212') }}</button>
        </div>
    </section>
</div>
