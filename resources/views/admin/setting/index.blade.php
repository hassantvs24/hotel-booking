<x-admin.layout title="Settings">
    <x-admin.card>
        <x-admin.card.card-header title="Settings" class="d-flex align-content-center">
            <x-admin.page-action>
                <a href="#" class="m-0 btn btn-xs btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add Setting
                </a>
            </x-admin.page-action>
        </x-admin.card.card-header>

        <x-admin.card.card-body>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="settings">Setting To Edit</label>
                        <select id="settings" style="width: 100%">
                            <option>Select a Setting</option>
                            @foreach($settings as $key => $setting)
                                <optgroup label="{{ \Illuminate\Support\Str::ucfirst($key) }}">
                                    @foreach($setting as $coreSetting)
                                        <option
                                            value="{{ $coreSetting->id }}"
                                            class="core_setting"
                                            data-item="{{ $coreSetting }}"
                                        >
                                            {{ $coreSetting->key }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="setting_container mt-2">
                        <code id="preview_value"></code>
                        <div class="setting_item">
                            <div class="setting_title">Key</div>
                            <div class="setting_value">Value</div>
                        </div>

                        <div class="setting_item">
                            <div class="setting_title">Group</div>
                            <div class="setting_value">
                                <select name="value_type">
                                    <option value="text">Text</option>
                                    <option value="bool">Boolean</option>
                                    <option value="video">Video</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                        </div>
                        <div class="setting_item">
                            <div class="setting_title">Value Type</div>
                            <div class="setting_value">
                                <select name="value_type">
                                    <option value="text">Text</option>
                                    <option value="bool">Boolean</option>
                                    <option value="video">Video</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                        </div>
                        <div class="setting_item">
                            <div class="setting_title">Value</div>
                            <div class="setting_value">Value</div>
                        </div>
                        <div class="setting_item">
                            <div class="setting_title">Key</div>
                            <div class="setting_value">Value</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.card.card-body>

    </x-admin.card>

    @push('scripts')
        <script>
            let selectElement = $('#settings');

            $(document).ready(function () {

                selectElement.on('select2:select', function (e) {
                    let selectedOption = e.params.data;
                    let item = $(selectedOption.element).data('item');

                    let previewContainer = $('#preview_value');

                    previewContainer.html(item.value)

                    console.log(item);
                });


                function formatOption(option) {
                    if (!option.id) {
                        return option.text;
                    }

                    let $option = $(
                        '<div>' + option.text + '</div>'
                    );

                    return $option;
                }

                selectElement.select2({
                    templateResult: formatOption,
                });
            });
        </script>

    @endpush
</x-admin.layout>