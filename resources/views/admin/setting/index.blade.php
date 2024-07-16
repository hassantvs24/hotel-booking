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

                @php
                    $valueTypes = [
                       'text' => 'Text',
                       'bool' => 'Boolean',
                       'image' => 'Image',
                       'video' => 'Video'
                    ];
                @endphp

                <div class="col-md-12">
                    <x-admin.form action="" method="PUT">
                        <div class="setting_container mt-2">
                            <table class="table table-bordered">
                                <tbody>
                                <tr>
                                    <th>Key</th>
                                    <td>
                                        <x-admin.input
                                            type="text"
                                            name="key"
                                            id="setting_key"
                                            placeholder="Enter setting Key"
                                            :disabled="true"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Value Type</th>
                                    <td>
                                        <x-admin.input
                                            type="select"
                                            name="value_type"
                                            id="setting_value_type"
                                            :options="$valueTypes"
                                            :disabled="true"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Value</th>
                                    <td>
                                        <x-admin.input
                                            type="textarea"
                                            name="value"
                                            id="setting_value_text"
                                            placeholder="Enter Value"
                                        />
                                        <x-admin.input
                                            type="toggle"
                                            name="value"
                                            id="setting_value_boolean"
                                        />
                                        <x-admin.input
                                            type="file"
                                            name="value"
                                            id="setting_value_image"
                                        />
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <x-admin.button
                                class="float-right"
                                type="submit"
                                size="sm"
                                text="Save Changes"
                            />
                        </div>
                    </x-admin.form>
                </div>
            </div>
        </x-admin.card.card-body>

    </x-admin.card>

    @push('scripts')
        <script>
            let selectElement = $('#settings');

            let keyField = $('#setting_key');
            let valueTypeField = $('#setting_value_type');
            let valueTextField = $('#setting_value_text');
            let valueBooleanField = $('#setting_value_boolean');
            let valueImageField = $('#setting_value_image');


            function hideAllFields() {
                keyField.hide();
                valueTypeField.hide();
                valueTextField.hide();
                valueBooleanField.hide();
                valueImageField.hide();
            }

            function showSettingFields(valueType = null) {
                hideAllFields();
                keyField.show();
                valueTypeField.show();

                if (!valueType) return;

                switch (valueType) {
                    case 'text':
                        valueTextField.show();
                        break;
                    case 'bool':
                        valueBooleanField.show();
                        break;
                    case 'image':
                        valueImageField.show();
                        break;
                    default:
                        valueTextField.show();
                        break;
                }
            }

            hideAllFields();

            function setFieldValue(element, value) {
                if (!element || !value) return;
                element.val(value)
            }

            function populateValues(item) {
                if (!item) return;

                setFieldValue(keyField, item.key);
                setFieldValue(valueTypeField, item.value_type);

                if (item.value_type === 'text') {
                    setFieldValue(valueTextField, item.value);
                } else if (item.value_type === 'bool') {
                    setFieldValue(valueBooleanField, item.value);
                } else if (item.value_type === 'image') {
                    setFieldValue(valueImageField, item.value);
                } else {
                    setFieldValue(valueTextField, item.value);
                }


            }

            $(document).ready(function () {

                selectElement.on('select2:select', function (e) {
                    let selectedOption = e.params.data;
                    let item = $(selectedOption.element).data('item');

                    showSettingFields(item.value_type);
                    populateValues(item);
                });


                valueTypeField.on('change', function (e) {
                    showSettingFields(this.value);
                })


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