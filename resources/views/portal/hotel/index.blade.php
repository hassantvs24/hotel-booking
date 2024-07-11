<x-portal.layout title="Hotels">
    <section class="room-search">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    
                    <div class="room-search-number">
                        <h4>{{$hotelsResponded}} hotel responded - 18 available rooms</h4>
                        <img src="{{asset('assets/portal/img/icons/icon-info.png')}}" alt="">
                    </div>
                    @forelse ($properties as $index=> $property)
                    <div class="room-search-item">
                        <div class="room-column-slider">
                   <span class="room-column-icon">
                   <a href=""><img src="{{asset('assets/portal/img/icons/icon-heart.png')}}" alt=""></a>
                   </span>
                            <div id="room-slider-{{ $index + 1 }}" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    @foreach ($property->images as $key => $image)
                                    <button type="button" data-bs-target="#room-slider-{{ $index + 1 }}" data-bs-slide-to="{{ $key }}" class="{{ $key === 0 ? 'active' : '' }}" aria-current="{{ $key === 0 ? 'true' : 'false' }}"></button>
                                    @endforeach
                                </div>
                                <div class="carousel-inner">
                                    {{-- @foreach ($property->images as $key => $image) --}}
                                        {{-- <div class="carousel-item {{ $key === 0 ? 'active' : '' }}"> --}}
                                            <img src="{{$property->primary_image_url}}" class="d-block w-100 h-100" alt="...">
                                        {{-- </div> --}}
                                    {{-- @endforeach --}}
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#room-slider-{{ $index + 1 }}" data-bs-slide="prev">
                      <span class="carousel-control-prev-icon" aria-hidden="true">
                      <img src="{{asset('assets/portal/img/icons/arrow_back.png')}}" alt="">
                      </span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#room-slider-{{ $index + 1 }}" data-bs-slide="next">
                      <span class="carousel-control-next-icon" aria-hidden="true">
                      <img src="{{asset('assets/portal/img/icons/arrow_forward.png')}}" alt="">
                      </span>
                                </button>
                            </div>
                        </div>
                        <div class="room-search-box">
                            <div class="room-search-top d-flex justify-content-between align-items-start">
                                <div class="room-search-top-info">
                                    <span><img src="{{asset('assets/portal/img/icons/icon-home.png')}}" alt=""> Hotel</span>
                                    <h4>{{$property->name}}</h4>
                                    <p><img src="{{asset('assets/portal/img/icons/icon-pin.png')}}" alt=""> {{$property->place?->name}},{{$property->city?->name}}</p>
                                    <h6>2+ rooms are available</h6>
                                </div>
                                <div class="room-search-top-rating d-flex justify-content-between align-items-center">
                                    <h5>Rating<span>1234 reviews</span></h5>
                                    <p>4.8</p>
                                </div>
                            </div>
                            <div class="room-search-bottom d-flex justify-content-between align-items-end">
                                <div class="room-search-features">
                                    <span><img src="{{asset('assets/portal/img/icons/icon-welcome-drink.png')}}" alt=""> Welcome Drink</span>
                                    <span><img src="{{asset('assets/portal/img/icons/icon-breakfast.png')}}" alt=""> Free Breakfast</span>
                                    <span><img src="{{asset('assets/portal/img/icons/icon-tea.png')}}" alt=""> Tea/Coffee</span>
                                    <span><img src="{{asset('assets/portal/img/icons/icon-air-condiitoner.png')}}" alt=""> Air Conditioner</span>
                                </div>
                                <div class="room-search-price">
                                    <div class="room-search-timer color-blue" data-date="November 22, 2023 21:14:01">
                                        <span><img src="{{asset('assets/portal/img/icons/icon-countdown-blue.svg')}}" alt=""></span>
                                        <span class="days">0</span>
                                        <span class="hours">0</span>
                                        <span class="minutes">0</span>
                                        <span class="seconds">0</span>
                                    </div>
                                    <span>Start From</span>
                                    <p><del>BDT 15,906</del> BDT 15,906</p>
                                    <a href="" class="btn-border" data-bs-toggle="modal" data-bs-target="#offer-modal-{{$index + 1}}">Offer</a>
                                    <a href="" class="btn-bg ms-2" data-bs-toggle="modal" data-bs-target="#accept-modal-{{ $index + 1 }}">Accept</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    @empty
                        <h5>no result found</h5>
                    @endforelse
                    
                    <!-- Modal Offer -->
                    @foreach ($properties as $index => $property)
                    <div class="modal fade" id="offer-modal-{{ $index + 1 }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content offer">
                                <div class="modal-header">
                                    <h4 class="modal-title fs-5" id="exampleModalLabel">Seacrest Oceanfront Resort</h4>
                                    <span>
                            Details
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                               <mask id="mask0_415_2799" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="18" height="19">
                                  <rect y="0.5" width="18" height="18" fill="#D9D9D9"></rect>
                               </mask>
                               <g mask="url(#mask0_415_2799)">
                                  <path d="M11.9998 6.8L5.34355 13.475C5.19355 13.625 5.0153 13.7 4.8088 13.7C4.6028 13.7 4.4248 13.625 4.2748 13.475C4.1248 13.325 4.0498 13.1468 4.0498 12.9403C4.0498 12.7343 4.1248 12.5563 4.2748 12.4063L10.9498 5.75H5.2498C5.0373 5.75 4.85905 5.67825 4.71505 5.53475C4.57155 5.39075 4.4998 5.2125 4.4998 5C4.4998 4.7875 4.57155 4.60925 4.71505 4.46525C4.85905 4.32175 5.0373 4.25 5.2498 4.25H12.7498C12.9623 4.25 13.1403 4.32175 13.2838 4.46525C13.4278 4.60925 13.4998 4.7875 13.4998 5V12.5C13.4998 12.7125 13.4278 12.8905 13.2838 13.034C13.1403 13.178 12.9623 13.25 12.7498 13.25C12.5373 13.25 12.3593 13.178 12.2158 13.034C12.0718 12.8905 11.9998 12.7125 11.9998 12.5V6.8Z" fill="#0284C7"></path>
                               </g>
                            </svg>
                         </span>
                                </div>
                                <div class="modal-body">
                                    <div id="offer-carousel-1" class="carousel slide">
                                        <div class="carousel-inner">
                                            <div class="carousel-item active">
                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="carousel-item">
                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="carousel-item">
                                                <div class="container">
                                                    <div class="container">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#offer-carousel-1" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true">
                            <img src="{{asset('assets/portal/img/icons/arrow_back.png')}}" alt="">
                            </span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#offer-carousel-1" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true">
                            <img src="{{asset('assets/portal/img/icons/arrow_forward.png')}}" alt="">
                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @foreach ($properties as $index => $property)
                    <!-- Modal Accept -->
                    <div class="modal fade" id="accept-modal-{{ $index + 1 }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content offer">
                                <div class="modal-header">
                                    <h4 class="modal-title fs-5" id="exampleModalLabel">Seacrest Oceanfront Resort</h4>
                                    <span>
                            Details
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                               <mask id="mask0_415_2799" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="18" height="19">
                                  <rect y="0.5" width="18" height="18" fill="#D9D9D9"></rect>
                               </mask>
                               <g mask="url(#mask0_415_2799)">
                                  <path d="M11.9998 6.8L5.34355 13.475C5.19355 13.625 5.0153 13.7 4.8088 13.7C4.6028 13.7 4.4248 13.625 4.2748 13.475C4.1248 13.325 4.0498 13.1468 4.0498 12.9403C4.0498 12.7343 4.1248 12.5563 4.2748 12.4063L10.9498 5.75H5.2498C5.0373 5.75 4.85905 5.67825 4.71505 5.53475C4.57155 5.39075 4.4998 5.2125 4.4998 5C4.4998 4.7875 4.57155 4.60925 4.71505 4.46525C4.85905 4.32175 5.0373 4.25 5.2498 4.25H12.7498C12.9623 4.25 13.1403 4.32175 13.2838 4.46525C13.4278 4.60925 13.4998 4.7875 13.4998 5V12.5C13.4998 12.7125 13.4278 12.8905 13.2838 13.034C13.1403 13.178 12.9623 13.25 12.7498 13.25C12.5373 13.25 12.3593 13.178 12.2158 13.034C12.0718 12.8905 11.9998 12.7125 11.9998 12.5V6.8Z" fill="#0284C7"></path>
                               </g>
                            </svg>
                         </span>
                                </div>
                                <div class="modal-body">
                                    <div id="offer-carousel-2" class="carousel slide">
                                        <div class="carousel-inner">
                                            <div class="carousel-item active">
                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="carousel-item">
                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="single-room-modal">
                                                                <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                <div class="room-modal-content">
                                                                    <h4>Superior Twin Room</h4>
                                                                    <ul>
                                                                        <li>Breakfast for 4</li>
                                                                        <li>Welcome Drink</li>
                                                                        <li>Beach view</li>
                                                                        <li>A/C</li>
                                                                        <li>and more</li>
                                                                    </ul>
                                                                    <div class="room-modal-bottom">
                                                                        <del>BDT 19,500</del>
                                                                        <p>19,500 <span>/night</span></p>
                                                                        <a href="" class="btn-bg">Make An Offer</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="carousel-item">
                                                <div class="container">
                                                    <div class="container">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-3.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-1.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="single-room-modal">
                                                                    <img src="{{asset('assets/portal/img/rooms/room-2.png')}}" alt="">
                                                                    <div class="room-modal-content">
                                                                        <h4>Superior Twin Room</h4>
                                                                        <ul>
                                                                            <li>Breakfast for 4</li>
                                                                            <li>Welcome Drink</li>
                                                                            <li>Beach view</li>
                                                                            <li>A/C</li>
                                                                            <li>and more</li>
                                                                        </ul>
                                                                        <div class="room-modal-bottom">
                                                                            <del>BDT 19,500</del>
                                                                            <p>19,500 <span>/night</span></p>
                                                                            <a href="" class="btn-bg">Make An Offer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#offer-carousel-2" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true">
                            <img src="{{asset('assets/portal/img/icons/arrow_back.png')}}" alt="">
                            </span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#offer-carousel-2" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true">
                            <img src="{{asset('assets/portal/img/icons/arrow_forward.png')}}" alt="">
                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
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