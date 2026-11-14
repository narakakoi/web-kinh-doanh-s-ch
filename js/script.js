// Lấy các phần tử từ DOM
const userBox = document.querySelector(".header .flex .account-box");
const navbar = document.querySelector(".header .flex .navbar");
const userBtn = document.querySelector("#user-btn");
const menuBtn = document.querySelector("#menu-btn");

// Định nghĩa sự kiện click cho nút user
userBtn?.addEventListener("click", () => {
  userBox?.classList.toggle("active");
  navbar?.classList.remove("active");
});

// Định nghĩa sự kiện scroll cho window
window.addEventListener("scroll", () => {
  userBox?.classList.remove("active");
  navbar?.classList.remove("active");
});

// Định nghĩa sự kiện click cho nút menu
menuBtn?.addEventListener("click", () => {
  navbar?.classList.toggle("active");
  userBox?.classList.remove("active");
});
