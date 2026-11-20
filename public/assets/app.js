(() => {
  const form = document.querySelector('#checkin-form');
  if (!form) return;
  const photo = document.querySelector('#photo');
  const latitude = document.querySelector('#latitude');
  const longitude = document.querySelector('#longitude');
  const locationMessage = document.querySelector('#location-message');
  const saveButton = document.querySelector('#save-button');
  const uploadMessage = document.querySelector('#upload-message');
  let locationReady = false;
  let photoReady = false;
  const ready = () => { saveButton.disabled = !(locationReady && photoReady); };
  navigator.geolocation.getCurrentPosition((position) => {
    latitude.value = position.coords.latitude; longitude.value = position.coords.longitude;
    locationReady = true; locationMessage.textContent = 'GPS location captured.'; ready();
  }, () => { locationMessage.textContent = 'GPS is required. Allow location permission, then reload this page.'; }, {enableHighAccuracy: true, timeout: 20000, maximumAge: 0});
  photo.addEventListener('change', () => {
    const file = photo.files[0]; if (!file) return;
    if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 5 * 1024 * 1024) { locationMessage.textContent = 'Use a JPEG or PNG photo smaller than 5 MB.'; photo.value = ''; return; }
    const image = new Image(); image.onload = () => {
      const longest = Math.max(image.width, image.height); const ratio = Math.min(1, 1280 / longest); const canvas = document.createElement('canvas');
      canvas.width = Math.round(image.width * ratio); canvas.height = Math.round(image.height * ratio); canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
      canvas.toBlob((blob) => { if (!blob) return; const resized = new File([blob], 'checkin.jpg', {type: 'image/jpeg'}); const files = new DataTransfer(); files.items.add(resized); photo.files = files.files; photoReady = true; ready(); }, 'image/jpeg', 0.8);
    }; image.src = URL.createObjectURL(file);
  });
  form.addEventListener('submit', () => { saveButton.disabled = true; uploadMessage.hidden = false; });
})();
