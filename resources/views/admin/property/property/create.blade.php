<x-admin.layout title="Add New Property">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Property"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.properties.store') }}" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="status" id="status" />
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="Property Name"
                            label="Property Name"
                            value="{{ old('name') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <x-admin.input
                            type="select"
                            name="property_category_id"
                            id="property_category_id"
                            label="Property Category"
                            :options="$propertyCategories"
                            additional-classes="searchable"
                        />
                    </div>
                    <div class="col-4">
                        <x-admin.input
                            type="select"
                            name="property_class"
                            id="property_class"
                            label="Property Standard"
                            :options="$propertyClasses"
                        />
                    </div>
                    <div class="col-4">
                        <x-admin.input
                            type="number"
                            name="rating"
                            id="rating"
                            placeholder="Rating"
                            label="Rating"
                            value="{{ old('rating') ?: 0 }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-3">
                        <x-admin.input
                            type="select"
                            name="place_id"
                            id="place_id"
                            label="Place"
                            :options="$places"
                            additional-classes="searchable"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-admin.input
                            type="number"
                            name="lat"
                            id="lat"
                            placeholder="Latitude"
                            label="Latitude"
                            value="{{ old('lat') }}"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-admin.input
                            type="number"
                            name="long"
                            id="long"
                            placeholder="Longitude"
                            label="Longitude"
                            value="{{ old('long') }}"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-admin.input
                            type="text"
                            name="zip_code"
                            id="zip_code"
                            placeholder="Zip Code"
                            label="Zip Code"
                            value="{{ old('zip_code') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <x-admin.input
                        type="time"
                        name="check_in_time"
                        id="check_in_time"
                        placeholder="Check In"
                        label="Check In"
                        value="{{ old('check_in_time') }}"
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="time"
                            name="check_out_time"
                            id="check_out_time"
                            placeholder="Check Out"
                            label="Check Out"
                            value="{{ old('check_out_time') }}"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="text"
                            name="phone_number"
                            id="phone_number"
                            placeholder="Phone Number"
                            label="Phone Number"
                            value="{{ old('phone_number') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <x-admin.input
                        type="text"
                        name="address"
                        id="address"
                        placeholder="Address"
                        label="Address"
                        value="{{ old('address') }}"
                    />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="number"
                            name="total_room"
                            id="total_room"
                            placeholder="Total Room"
                            label="Total Room"
                            value="{{ old('total_room') ?: 0 }}"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-admin.input
                            type="text"
                            name="currency"
                            id="currency"
                            placeholder="Currency"
                            label="Currency"
                            value="{{ old('currency') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="textarea"
                        name="google_review"
                        id="google_review"
                        placeholder="Google Review"
                        label="Google Review"
                        value="{{ old('google_review') }}"
                    />
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <x-admin.input
                            type="textarea"
                            name="seo_title"
                            id="seo_title"
                            label="Seo Title"
                            placeholder="Seo Title"
                            value="{{ old('seo_title') }}"
                        />
                    </div>
                    <div class="col-6">
                        <x-admin.input
                            type="textarea"
                            name="seo_meta"
                            id="seo_meta"
                            placeholder="Seo Meta"
                            label="Seo Meta"
                            value="{{ old('seo_meta') }}"
                        />
                    </div>
                    <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                        type="textarea"
                        name="description"
                        id="description"
                        placeholder="Description"
                        label="Description"
                        value="{{ old('description') }}"
                    />
                    </div>
                </div>

                    <div class="col-md-12">
                        <label for="property_facilities">Set Property Facilities</label>
                        <x-admin.input
                            type="select"
                            name="property_facilities[]"
                            id="property_facilities"
                            placeholder="Facilities"
                            multiple
                            :options="$facilities"
                        />
                    </div>
                </div>

{{--                <div class="row">--}}
{{--                    <div class="col-md-12">--}}
{{--                        <h6>Property Rules Setup</h6>--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-md-3">--}}
{{--                                <x-admin.input--}}
{{--                                    type="text"--}}
{{--                                    name="rules[description][]"--}}
{{--                                    id="rules_description"--}}
{{--                                    label="Description"--}}
{{--                                    placeholder="Description"--}}
{{--                                />--}}
{{--                            </div>--}}
{{--                            <div class="col-md-2">--}}
{{--                                <x-admin.input--}}
{{--                                    type="select"--}}
{{--                                    name="rules[property_rule_id][]"--}}
{{--                                    id="rules_property_rule_id"--}}
{{--                                    label="Rule"--}}
{{--                                    :options="[]"--}}
{{--                                />--}}
{{--                            </div>--}}
{{--                            <div class="col-md-2">--}}
{{--                                <x-admin.input--}}
{{--                                    type="select"--}}
{{--                                    name="rules[property_rule_id][]"--}}
{{--                                    id="rules_property_rule_id"--}}
{{--                                    label="Rule"--}}
{{--                                    :options="[]"--}}
{{--                                />--}}
{{--                            </div>--}}
{{--                            <div class="col-md-2">--}}
{{--                                <x-admin.input--}}
{{--                                    type="select"--}}
{{--                                    name="rules[property_rule_id][]"--}}
{{--                                    id="rules_property_rule_id"--}}
{{--                                    label="Rule"--}}
{{--                                    :options="[]"--}}
{{--                                />--}}
{{--                            </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="file"
                            name="photo"
                            id="photo"
                            placeholder="Primary Image"
                            label="Primary Image"
                            value="{{ old('photo') }}"
                        />
                    </div>
                    <div class="col-md-12">
                        <img width="50" height="50" src="" id="preview_icon" alt=""/>
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.properties.index') }}" class="btn btn-danger btn-sm">Back To List</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button class="submit_button" data-value="Pending" variant="info" type="submit" size="sm">Pending</x-admin.button>
                        <x-admin.button class="submit_button" data-value="Unpublished" variant="warning" type="submit" size="sm">Un-Publish</x-admin.button>
                        <x-admin.button class="submit_button" data-value="Published" variant="primary" type="submit" size="sm">Published</x-admin.button>
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
                    $('#preview_icon').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            $('#property_facilities').select2({
                placeholder: "Select Facilities",
                allowClear: true
            });
        });
    </script>
        <script>
            $('.submit_button').on('click', function (e) {
                e.preventDefault();
                let status = $(this).data('value');
                $('#status').val(status);

                $(this).closest('form').submit();
            });
        </script>
    @endpush
</x-admin.layout>
