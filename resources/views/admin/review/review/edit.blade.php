<x-admin.layout title="Update Review">
    <x-admin.card>
        <x-admin.card.card-header title="Update Review"/>
        <x-admin.card.card-body>
            <x-admin.form action="{{ route('admin.reviews.update', $review->id) }}" method="PUT">
                <div class="row">
                    <div class="col-md-12">
                        <x-admin.input
                            type="number"
                            name="rating"
                            id="rating"
                            placeholder="Rating"
                            label="Rating"
                            value="{{$review->rating}}"
                        />
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.input
                        type="select"
                        name="review_category_id"
                        id="review_category_id"
                        label="Review Category"
                        :options="$reviewCategories"
                        :value="$review->review_category_id"
                        additional-classes="searchable"
                    />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input
                            type="select"
                            name="property_id"
                            id="property_id"
                            label="Property"
                            :options="$properties"
                            :value="$review->property_id"
                            additional-classes="searchable"
                        />
                    </div>
                </div>
                <div class="mt-3 row">
                    <div class="col-md-6">
                        <a href="{{ route('admin.reviews.index') }}" class="btn btn-danger btn-sm">Back To List</a>
                    </div>
                    <div class="text-right col-md-6">
                        <x-admin.button variant="primary" type="submit" size="sm">Update Review</x-admin.button>
                    </div>
                </div>
            </x-admin.form>
        </x-admin.card.card-body>
    </x-admin.card>
</x-admin.layout>
