<x-portal.layout title="Payment">
<section class="payment">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h4 class="page-title">Confirm & Pay</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-6 col-md-12">
                <div class="booking-information">
                    <h4 class="page-subtitle">Your Booking Information</h4>
                    <ul>
                        <li><img src="{{asset('assets/portal/img/icons/icon-range.svg')}}" alt=""> {{$checkInDateFormatted}} To {{$checkOutDateFormatted}}  {{$numberOfNights?$numberOfNights:'0'}} nights</li>
                        <li><img src="{{asset('assets/portal/img/icons/icon-persons.svg')}}" alt=""> {{$numberAdults?$numberAdults:'0'}} Adult + {{$numberChildren?$numberChildren:'0'}} Child</li>
                    </ul>
                </div>

                <div class="payment-form-box">
                    <h4 class="page-subtitle">Pay With</h4>
                    <div class="single-step-radio-image">
                        <div class="step-radio-image-box">
                            <input type="radio" name="payment" id="trade">
                            <label for="trade">
                                <img src="{{asset('assets/portal/img/icons/payment-card.svg')}}" alt="">
                                <span>Credit Card</span>
                            </label>
                        </div>
                        <div class="step-radio-image-box">
                            <input type="radio" name="payment" id="tin">
                            <label for="tin">
                                <img src="{{asset('assets/portal/img/icons/payment-bkash.svg')}}" alt="">
                                <span>bKash</span>
                            </label>
                        </div>
                        <div class="step-radio-image-box">
                            <input type="radio" name="payment" id="others">
                            <label for="others">
                                <img src="{{asset('assets/portal/img/icons/payment-nagad.svg')}}" alt="">
                                <span>Nagad</span>
                            </label>
                        </div>
                        <div class="step-radio-image-box">
                            <input type="radio" name="payment" id="others1">
                            <label for="others1">
                                <img src="{{asset('assets/portal/img/icons/payment-dbbl.svg')}}" alt="">
                                <span>DBBL Nesux</span>
                            </label>
                        </div>
                    </div>

                    <div class="payment-form">
                        <input type="text" placeholder="Card Holder Name">
                        <input type="text" placeholder="Card Number">
                        <div class="payment-form-half">
                            <input type="text" placeholder="Expiration">
                            <input type="text" placeholder="CVV">
                        </div>
                    </div>

                    <div class="payment-form-select">
                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_294_3834" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25">
                        <rect y="0.5" width="24" height="24" fill="#D9D9D9"/>
                        </mask>
                        <g mask="url(#mask0_294_3834)">
                        <path d="M12 22.5C10.6167 22.5 9.31667 22.2373 8.1 21.712C6.88333 21.1873 5.825 20.475 4.925 19.575C4.025 18.675 3.31267 17.6167 2.788 16.4C2.26267 15.1833 2 13.8833 2 12.5C2 11.1167 2.26267 9.81667 2.788 8.6C3.31267 7.38333 4.025 6.325 4.925 5.425C5.825 4.525 6.88333 3.81233 8.1 3.287C9.31667 2.76233 10.6167 2.5 12 2.5C14.4333 2.5 16.5623 3.26233 18.387 4.787C20.2123 6.31233 21.35 8.225 21.8 10.525H19.75C19.4333 9.30833 18.8627 8.22067 18.038 7.262C17.2127 6.304 16.2 5.58333 15 5.1V5.5C15 6.05 14.8043 6.52067 14.413 6.912C14.021 7.304 13.55 7.5 13 7.5H11V9.5C11 9.78333 10.9043 10.0207 10.713 10.212C10.521 10.404 10.2833 10.5 10 10.5H8V12.5H10V15.5H9L4.2 10.7C4.15 11 4.104 11.3 4.062 11.6C4.02067 11.9 4 12.2 4 12.5C4 14.6833 4.76667 16.5583 6.3 18.125C7.83333 19.6917 9.73333 20.4833 12 20.5V22.5ZM20.4 21.3L17.9 18.8C17.55 19 17.175 19.1667 16.775 19.3C16.375 19.4333 15.95 19.5 15.5 19.5C14.25 19.5 13.1873 19.0627 12.312 18.188C11.4373 17.3127 11 16.25 11 15C11 13.75 11.4373 12.6873 12.312 11.812C13.1873 10.9373 14.25 10.5 15.5 10.5C16.75 10.5 17.8127 10.9373 18.688 11.812C19.5627 12.6873 20 13.75 20 15C20 15.45 19.9333 15.875 19.8 16.275C19.6667 16.675 19.5 17.05 19.3 17.4L21.8 19.9C21.9833 20.0833 22.075 20.3167 22.075 20.6C22.075 20.8833 21.9833 21.1167 21.8 21.3C21.6167 21.4833 21.3833 21.575 21.1 21.575C20.8167 21.575 20.5833 21.4833 20.4 21.3ZM15.5 17.5C16.2 17.5 16.7917 17.2583 17.275 16.775C17.7583 16.2917 18 15.7 18 15C18 14.3 17.7583 13.7083 17.275 13.225C16.7917 12.7417 16.2 12.5 15.5 12.5C14.8 12.5 14.2083 12.7417 13.725 13.225C13.2417 13.7083 13 14.3 13 15C13 15.7 13.2417 16.2917 13.725 16.775C14.2083 17.2583 14.8 17.5 15.5 17.5Z" fill="#14B8A6"/>
                        </g>
                        </svg>
                        <select name="" id="">
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                            <option value="">Bangladesh</option>
                        </select>
                    </div>
                </div>

                <div class="payment-coupon-form">
                    <button id="payment-btn">Enter Coupon</button>
                    <input type="text" placeholder="Coupon Code" id="theParagraph" class="hidden">
                </div>

                @if (session('message'))
                <div class="alert alert-{{ session('alert-type') }}">
                    {{ session('message') }}
                </div>
            @endif
                <div class="booking-cancel">
                    <form action="{{route('portal.booking.store',$room->id)}}" method="POST">
                    @csrf
                    <h4 class="page-subtitle">Cancellation policy</h4>
                    <p>Cancel before check-in on {{$checkInDateFormatted}} for a partial refund. After that, this reservation is non-refundable. Learn more</p>

                    <div class="booking-agree">
                        <input type="checkbox" name="agree1" id="agree1" class="{{ isset($error) ? 'error-alert' : '' }}">
                        <label for="agree1">I agree to the <a href="">property rules</a> for guests, Rate Locker’s booking and <a href="">refund policy</a>, and you will be responsible for any property damage.</label>
                    </div>

                    <div class="booking-agree">
                        <input type="checkbox" name="agree2" id="agree2" class="{{ isset($error) ? 'error-alert' : '' }}">
                        <label for="agree2">I also agree to the <a href="">Terms of Service</a>, <a href="">Payments Terms of Service</a>, and I acknowledge the <a href="">Privacy Policy</a>.</label>
                    </div>
                    <button type="submit" class="btn-bg">Confirm Booking & Pay</button>
                </form>
                </div>
            </div>
            <div class="col-xl-6 col-md-12 mt-md-3 mt-xl-0">
                <div class="booking-summary">
                    <div class="booking-summary-top">
                        <img src="{{$room->property->primary_image_url}}" alt="{{$room->property->name}}">
                        <div class="booking-summary-info">
                            <span><img src="{{asset('assets/portal/img/icons/icon-home.png')}}" alt="">Hotel</span>
                            <h4>{{$room->property->name}}</h4>
                            <p><span>Room:</span> {{$room->name}}</p>
                        </div>
                    </div>
                    <h5>Price Details</h5>
                    <ul>
                        <li>R#1 - BDT {{$room->base_price}} X {{$numberOfNights}}nights <span>BDT {{$price}}</span></li>
                        <li>VAT & Charges (5% + 7%) <span>BDT 3,577</span></li>
                        <li>Sub Total <span>BDT 33,389</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
</x-portal.layout>
