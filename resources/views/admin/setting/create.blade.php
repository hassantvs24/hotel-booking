<x-admin.layout title="Add Setting">
    <x-admin.card>
        <x-admin.card.card-header title="Add Setting"/>
        <x-admin.card.card-body>
            <x-admin.form
                    action="{{ route('admin.settings.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
            >
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                                type="text"
                                name="key"
                                id="key"
                                placeholder="Enter Key"
                                label="Setting Key"
                                value="{{ old('key') }}"
                        />
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
                        <x-admin.input
                                type="select"
                                name="value_type"
                                id="value_type"
                                label="Value Type"
                                :options="$valueTypes"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                                type="text"
                                name="value"
                                id="value"
                                placeholder="Value"
                                label="value"
                                value="{{ old('value') }}"
                        />
                    </div>
                </div>

                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-danger btn-sm">
                            Back To Lists
                        </a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">
                            Save Setting
                        </x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#photo').on('change', function () {
                    let reader = new FileReader();
                    reader.onload = (e) => {
                        $('#preview_photo').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(this.files[0]);
                });
            });
        </script>
    @endpush
</x-admin.layout>
