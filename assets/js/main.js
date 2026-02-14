// ハンバーガーメニュー
window.onload = function () {
    var nav = document.getElementById('nav-wrapper')
    var hamburger = document.getElementById('js-hamburger')
    var blackBg = document.getElementById('js-black-bg')

    hamburger.addEventListener('click', function () {
        nav.classList.toggle('open')
    })
    blackBg.addEventListener('click', function () {
        nav.classList.remove('open')
    })
}

// スライドショー
// document.addEventListener("DOMContentLoaded", function () {
//     const sliderWrapper = document.querySelector(".slider-wrapper");
//     const slides = document.querySelectorAll(".slider-wrapper img");
//     const slideCount = slides.length;
//     const visibleSlides = 3;
//     let currentIndex = 0;

//     for (let i = 0; i < visibleSlides; i++) {
//         const clone = slides[i].cloneNode(true);
//         sliderWrapper.appendChild(clone);
//     }

//     function slide() {
//         currentIndex++;
//         const offset = -(currentIndex * (100 / visibleSlides));
//         sliderWrapper.style.transition = "transform 1s ease-in-out";
//         sliderWrapper.style.transform = `translateX(${offset}%)`;

//         if (currentIndex === slideCount) {
//             setTimeout(() => {
//                 sliderWrapper.style.transition = "none";
//                 sliderWrapper.style.transform = `translateX(0)`;
//                 currentIndex = 0;
//             }, 1000);
//         }
//     }

//     setInterval(slide, 8000);
// });

// document.addEventListener('DOMContentLoaded', function() {
//     let currentIndex = 0;
//     const images = document.querySelectorAll('.slider img');
//     const totalImages = images.length;

//     function showNextImage() {
//       currentIndex = (currentIndex + 1) % totalImages;
//       document.querySelector('.slider').style.transform = `translateX(-${currentIndex * 33.3}%)`;
//     }

//     setInterval(showNextImage, 8000);
//   });
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.querySelector('.slider')
    const images = document.querySelectorAll('.slider-images img')
    const prevBtn = document.querySelector('.prev-btn')
    const nextBtn = document.querySelector('.next-btn')
    let currentIndex = 0

    // スライドの数
    const totalImages = images.length

    // スライドの切り替え関数
    function changeSlide() {
        const offset = -currentIndex * 100 // 画像の幅（100%）でスライド
        slider.style.transform = `translateX(${offset}%)`
    }

    // 次のスライドに進む
    nextBtn.addEventListener('click', function () {
        currentIndex = (currentIndex + 1) % totalImages
        changeSlide()
    })

    // 前のスライドに戻る
    prevBtn.addEventListener('click', function () {
        currentIndex = (currentIndex - 1 + totalImages) % totalImages
        changeSlide()
    })

    // 自動スライドショー
    setInterval(function () {
        currentIndex = (currentIndex + 1) % totalImages
        changeSlide()
    }, 8000) // 8秒ごとにスライドを切り替える
})

// プルダウンメニュー
// document.addEventListener("DOMContentLoaded", () => {
//     const regionNames = document.querySelectorAll(".region-name");

//     regionNames.forEach(regionName => {
//         regionName.addEventListener("click", () => {
//             const prefectureList = regionName.nextElementSibling;
//             if (prefectureList.style.display === "none" || prefectureList.style.display === "") {
//                 prefectureList.style.display = "block";
//             } else {
//                 prefectureList.style.display = "none";
//             }
//         });
//     });
// });

document.addEventListener('DOMContentLoaded', () => {
    const regionNames = document.querySelectorAll('.region-name')

    regionNames.forEach((regionName) => {
        regionName.addEventListener('click', () => {
            const prefectureList = regionName.nextElementSibling
            const toggleIcon = regionName.querySelector('.toggle-icon')

            if (
                prefectureList.style.display === 'none' ||
                prefectureList.style.display === ''
            ) {
                prefectureList.style.display = 'block'
                toggleIcon.textContent = '▼' // アイコンを変更
            } else {
                prefectureList.style.display = 'none'
                toggleIcon.textContent = '▶' // アイコンを戻す
            }
        })
    })
})

document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.main_products li')
    const columns = 3 // 列数を指定（3列の場合）

    // 商品数を列数で割った余りを計算
    const totalItems = items.length
    const remainder = totalItems % columns

    // 最後の行の商品を取得
    const lastRowStartIndex =
        remainder === 0 ? totalItems - columns : totalItems - remainder

    // 最後の行の商品に下の線を消すクラスを追加
    for (let i = lastRowStartIndex; i < totalItems; i++) {
        items[i].classList.add('no-border-bottom')
    }

    // 各列の右端の商品に右の線を消すクラスを追加（列が3列の場合）
    if (columns === 3) {
        for (let i = 2; i < totalItems; i += columns) {
            items[i].classList.add('no-border-right')
        }
    }
})

// document.addEventListener('DOMContentLoaded', () => {
//     const requiredSigninLinks = document.querySelectorAll(
//         '.required_signin_link'
//     )
//     console.log(requiredSigninLinks)

//     requiredSigninLinks.forEach((link) => {
//         link.addEventListener('click', (ev) => {
//             if (!document.getElementById('Authenticated')) {
//                 ev.preventDefault()
//                 document.getElementById('signinModal').style.display = 'block'
//             }
//         })
//     })
// })
