$(function () {
    var slide_responsive = [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 3,
                infinite: true,
                dots: true
            }
        },
        {
            breakpoint: 600,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 2
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                //dots: true,
            }
        }
    ];

    $('#slide-top').slick({
        arrows: false,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: true,
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        responsive: slide_responsive
    });


    $('#banner-promo').slick({
        arrows: false,
        autoplay: true,
        autoplaySpeed: 6000,
        dots: false,
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: slide_responsive
    });


    $('#banner-meio').slick({
        arrows: false,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: true,
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        //responsive: slide_responsive
    });


    $('#slide-depoimento').slick({
        arrows: false,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: true,
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1
    });
    if ($('#slide-depoimento .item').length <= 0) {
        $('#elm-depoimento').remove();
    }


    $('#produto-oferta').slick({
        arrows: false,
        autoplay: false,
        autoplaySpeed: 5000,
        dots: true,
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 4,
        responsive: slide_responsive
    });
    if ($('#produto-oferta .box-produto').length <= 0) {
        $('#elm-produto-oferta').remove();
    }

    if ($('#produto-sugerido .box-produto').length <= 0) {
        $('#elm-produto-sugerido').remove();
    }
    $('#produto-destaque').slick({
        arrows: false,
        autoplay: true,
        autoplaySpeed: 2000,
        dots: true,
        infinite: true,
        //speed: 7000,
        slidesToShow: 4,
        slidesToScroll: 2,
        //centerMode: true,
        //centerPadding: '90px',
        responsive: slide_responsive
    });

    if ($('#produto-destaque .box-produto').length <= 0) {
        $('#elm-produto-destaque').remove();
    }


});