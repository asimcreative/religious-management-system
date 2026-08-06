{{--
    Import dialog: choose a file, decide what should happen to awkward rows,
    then continue to the preview.

    Submitting posts to the preview screen rather than importing. Nothing is
    written until the user has seen what the file would do and confirmed it —
    so closing this dialog, or losing the connection after submitting, cannot
    leave half a file in the database.

    Works without JavaScript: it is a plain multipart form inside a Bootstrap
    modal, and the sample-sheet link is an ordinary anchor.
--}}
@props(['definition', 'modalId'])

@php
    $maxMb = round((int) config('data-transfer.limits.max_upload_kb', 10240) / 1024, 1);
    $formats = strtoupper(implode(', ', config('data-transfer.allowed_extensions', [])));
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1"
     aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST"
              action="{{ route('data.import.preview', ['resource' => $definition->key()]) }}"
              enctype="multipart/form-data"
              class="modal-content"
              data-import-form>
            @csrf

            <div class="modal-header">
                <h2 class="modal-title h6" id="{{ $modalId }}-title">
                    <i class="bi bi-box-arrow-in-down text-brand" aria-hidden="true"></i>
                    <span>{{ __('data_transfer.import_title', ['module' => $definition->label()]) }}</span>
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('data_transfer.close') }}"></button>
            </div>

            <div class="modal-body">
                <p class="text-soft fs-sm">{{ __('data_transfer.import_intro') }}</p>

                {{-- File --}}
                <div class="mb-3">
                    <label for="{{ $modalId }}-file" class="form-label">
                        {{ __('data_transfer.choose_file') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>

                    <input type="file" name="file" id="{{ $modalId }}-file"
                           class="form-control @error('file') is-invalid @enderror"
                           accept=".xlsx,.xls,.csv"
                           required
                           aria-describedby="{{ $modalId }}-file-help">

                    <p class="form-text" id="{{ $modalId }}-file-help">
                        {{ __('data_transfer.file_help', ['formats' => $formats, 'size' => $maxMb.' MB']) }}
                    </p>

                    @error('file')
                        <p class="invalid-feedback d-block">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Template --}}
                <div class="import-hint">
                    <i class="bi bi-lightbulb" aria-hidden="true"></i>
                    <span>
                        {{ __('data_transfer.need_template') }}
                        <a href="{{ route('data.sample', ['resource' => $definition->key()]) }}">
                            {{ __('data_transfer.get_template') }}
                        </a>
                    </span>
                </div>

                {{-- Options --}}
                <fieldset class="mt-4">
                    <legend class="form-label mb-2">{{ __('data_transfer.mode') }}</legend>

                    @foreach (App\Support\DataTransfer\ImportMode::cases() as $mode)
                        <div class="form-check import-option">
                            <input class="form-check-input" type="radio" name="mode"
                                   id="{{ $modalId }}-mode-{{ $mode->value }}"
                                   value="{{ $mode->value }}"
                                   @checked($mode === App\Support\DataTransfer\ImportMode::SkipInvalid)>
                            <label class="form-check-label" for="{{ $modalId }}-mode-{{ $mode->value }}">
                                <span class="import-option__title">{{ $mode->label() }}</span>
                                <span class="import-option__help">{{ $mode->description() }}</span>
                            </label>
                        </div>
                    @endforeach
                </fieldset>

                <fieldset class="mt-3">
                    <legend class="form-label mb-2">{{ __('data_transfer.duplicate_handling') }}</legend>

                    @foreach (App\Support\DataTransfer\DuplicateStrategy::cases() as $strategy)
                        <div class="form-check import-option">
                            <input class="form-check-input" type="radio" name="duplicate_strategy"
                                   id="{{ $modalId }}-dup-{{ $strategy->value }}"
                                   value="{{ $strategy->value }}"
                                   @checked($strategy === App\Support\DataTransfer\DuplicateStrategy::Skip)>
                            <label class="form-check-label" for="{{ $modalId }}-dup-{{ $strategy->value }}">
                                <span class="import-option__title">{{ $strategy->label() }}</span>
                                <span class="import-option__help">{{ $strategy->description() }}</span>
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    {{ __('data_transfer.cancel') }}
                </button>

                <button type="submit" class="btn btn-primary btn-sm" data-submit-text="{{ __('data_transfer.validating') }}">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span>{{ __('data_transfer.validate') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
