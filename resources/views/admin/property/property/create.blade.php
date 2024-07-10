<x-admin.layout title="Add New Property">
    <x-admin.card>
        <x-admin.card.card-header title="Add New Property"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.properties.store') }}" method="POST" enctype="multipart/form-data">
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
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="lat"
                            id="lat"
                            placeholder="Latitude"
                            label="Latitude"
                            value="{{ old('lat') }}"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="number"
                            name="long"
                            id="long"
                            placeholder="Longitude"
                            label="Longitude"
                            value="{{ old('long') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
                        name="address"
                        id="address"
                        placeholder="Address"
                        label="Address"
                        value="{{ old('address') }}"
                    />
                    </div>
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="total_room"
                        id="total_room"
                        placeholder="Total Room"
                        label="Total Room"
                        value="{{ old('total_room') }}"
                        />
                    </div>
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <x-admin.input
                        type="number"
                        name="rating"
                        id="rating"
                        placeholder="Rating"
                        label="Rating"
                        value="{{ old('rating') }}"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                        type="text"
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
                            type="text"
                            name="seo_title"
                            id="seo_title"
                            label="Seo Title"
                            value="{{ old('seo_title') }}"
                        />
                    </div>
                    <div class="col-6">
                        <x-admin.input
                            type="text"
                            name="seo_meta"
                            id="seo_meta"
                            placeholder="Seo Meta"
                            label="Seo Meta"
                            value="{{ old('seo_meta') }}"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <x-admin.input
                            type="select"
                            name="property_class"
                            id="property_class"
                            label="Property Standard"
                            :options="$propertyClasses"
                        />
                    </div>
                    <div class="col-6">
                        <x-admin.input
                            type="select"
                            name="status"
                            id="status"
                            label="Status"
                            :options="$status"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <x-admin.input
                            type="select"
                            name="property_category_id"
                            id="property_category_id"
                            label="Property Category"
                            :options="$propertyCategories"
                            additional-classes="searchable"
                        />
                    </div>
                    <div class="col-6">
                        <x-admin.input
                            type="select"
                            name="place_id"
                            id="place_id"
                            label="Place"
                            :options="$places"
                            additional-classes="searchable"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="select"
                            name="property_facilities[]"
                            id="property_facilities"
                            label="Set Property Facilities"
                            multiple
                            :options="$facilities"
                        />
                    </div>
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
                        <x-admin.button variant="primary" type="submit" size="sm">Add Property</x-admin.button>
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
                $('#property_facilities').select2();
            });
        </script>
    @endpush
</x-admin.layout>
