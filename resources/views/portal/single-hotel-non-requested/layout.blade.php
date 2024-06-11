@extends('portal.layout.master')
@section("title","requested-waiting")
@section('content')


@include('portal.single-hotel-non-requested.rooms-section-title')
@include('portal.single-hotel-non-requested.room-gallery')
@include('portal.single-hotel-non-requested.room-amentites')
@include('portal.single-hotel-non-requested.single-room-column-area')
@include('portal.single-hotel-non-requested.single-room-column-area-two')
@include('portal.single-hotel-non-requested.about-property')
@include('portal.single-hotel-non-requested.reviews')
@include('portal.single-hotel-non-requested.amenities')
@include('portal.single-hotel-non-requested.policies')


@endsection
