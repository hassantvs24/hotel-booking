<x-portal.layout title="Properties">
    <section class="room-search">
        <div class="container">
           <div class="row">
              <div class="col-md-8">
                @forelse ($properties as $property)
                 <div class="room-search-item">
                    <div class="room-column-slider">
                       <span class="room-column-icon">
                       <a href=""><img src="assets/img/icons/icon-heart.png" alt=""></a>
                       </span>
                       <div id="room-slider-{{ $property->id }}" class="carousel slide" data-bs-ride="carousel">
                          <div class="carousel-indicators">
                            @foreach($property->images as $index => $image)
                             <button type="button" data-bs-target="#room-slider-{{ $property->id }}" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}"  aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                             @endforeach
                          </div>
                          <div class="carousel-inner">
                            {{-- @foreach($property->images as $index => $image) --}}
                             <div class="carousel-item active">
                                <img src="{{$property->primary_image_url}}" class="d-block w-100" alt="...">
                             </div>
                             {{-- @endforeach --}}
                          </div>
                          <button class="carousel-control-prev" type="button" data-bs-target="#room-slider-{{ $property->id }}" data-bs-slide="prev">
                          <span class="carousel-control-prev-icon" aria-hidden="true">
                          <img src="assets/img/icons/arrow_back.png" alt="">
                          </span>
                          </button>
                          <button class="carousel-control-next" type="button" data-bs-target="#room-slider-{{ $property->id }}" data-bs-slide="next">
                          <span class="carousel-control-next-icon" aria-hidden="true">
                          <img src="assets/img/icons/arrow_forward.png" alt="">
                          </span>
                          </button>
                       </div>
                    </div>
                    <div class="room-search-box">
                       <div class="room-search-top d-flex justify-content-between align-items-start">
                          <div class="room-search-top-info">
                             <span><img src="assets/img/icons/icon-home.png" alt=""> Hotel</span>
                             <h4>{{ $property->name }}</h4>
                             <p><img src="assets/img/icons/icon-pin.png" alt="">{{ $property->place?->name }}, {{ $property->place->city?->name }}</p>
                          </div>
                          <div class="room-search-top-rating d-flex justify-content-between align-items-center">
                             <h5>Rating<span>1234 reviews</span></h5>
                             <p>{{ $property->rating }}</p> 
                          </div>
                       </div>
                       <div class="room-search-bottom d-flex justify-content-between align-items-end">
                          <div class="room-search-features">
                           @foreach ($property->facilities as $facility)
                              <span><img src="{{$facility->icon_url}}" alt=""> {{$facility->name}}</span>
                           @endforeach
                          </div>
                          <div class="room-search-price">
                             @if ($property->lowest_room_price)
                                 <span>Price Starting From</span>
                                 <p>BDT {{ $property->lowest_room_price }}</p>
                             @endif
                             <a href="{{ route('portal.property.details', ['property'=>$property->id,'slug' => \Illuminate\Support\Str::slug($property->name)]) }}" class="btn-bg">Details</a>
                          </div>
                       </div>
                    </div>
                 </div>
                 @empty
                    <h4>No hotel found</h4>
                 @endforelse
              </div>
              <div class="col-md-4">
                 <div class="room-search-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d118830.24933512976!2d91.92050013258985!3d21.45103776040351!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30adc7ea2ab928c3%3A0x3b539e0a68970810!2sCox's%20Bazar!5e0!3m2!1sen!2sbd!4v1686734498443!5m2!1sen!2sbd"></iframe>
                 </div>
              </div>
           </div>
        </div>
     </section>
</x-portal.layout>