import {
  FilesetResolver,
  HandLandmarker,
  FaceLandmarker,
} from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14";

const config = window.TRYON_CONFIG;
const isHandMode = config.type === 'ring' || config.type === 'bracelet';

const video = document.getElementById('tryon-video');
const canvas = document.getElementById('tryon-canvas');
const ctx = canvas.getContext('2d');
const statusMsg = document.getElementById('statusMsg');
const startCamBtn = document.getElementById('startCamBtn');
const switchCamBtn = document.getElementById('switchCamBtn');
const photoInput = document.getElementById('photoInput');
const modeLiveBtn = document.getElementById('modeLiveBtn');
const modeUploadBtn = document.getElementById('modeUploadBtn');
const liveModeUI = document.getElementById('liveModeUI');
const uploadModeUI = document.getElementById('uploadModeUI');

let landmarker = null;
let landmarkerMode = null; // 'VIDEO' | 'IMAGE'
let currentFacing = 'user';
let stream = null;
let rafId = null;

// Which two landmarks (MCP, PIP) to use for each finger, so the ring can
// be placed on any finger the user picks, not just the ring finger.
const FINGER_LANDMARKS = {
  thumb: [2, 3],   // thumb only has MCP/IP, not a true PIP, but works the same way
  index: [5, 6],
  middle: [9, 10],
  ring: [13, 14],
  pinky: [17, 18],
};
let selectedFinger = 'ring';

function setFinger(fingerName) {
  if (!FINGER_LANDMARKS[fingerName]) return;
  selectedFinger = fingerName;
  document.querySelectorAll('.finger-btn').forEach(btn => {
    const isActive = btn.dataset.finger === fingerName;
    btn.classList.toggle('btn-dark', isActive);
    btn.classList.toggle('active', isActive);
    btn.classList.toggle('btn-outline-dark', !isActive);
  });
}
window.setFinger = setFinger;

const overlayImg = new Image();
overlayImg.crossOrigin = 'anonymous';
overlayImg.src = config.assetUrl;

canvas.width = 480;
canvas.height = 360;

function setMode(newMode) {
  if (newMode === 'live') {
    modeLiveBtn.classList.add('active');
    modeUploadBtn.classList.remove('active');
    liveModeUI.classList.remove('d-none');
    uploadModeUI.classList.add('d-none');
  } else {
    modeUploadBtn.classList.add('active');
    modeLiveBtn.classList.remove('active');
    uploadModeUI.classList.remove('d-none');
    liveModeUI.classList.add('d-none');
    stopCamera();
  }
}
window.setMode = setMode;

async function ensureLandmarker(runningMode) {
  if (landmarker && landmarkerMode === runningMode) return;

  const vision = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
  );

  if (isHandMode) {
    landmarker = await HandLandmarker.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task",
      },
      runningMode,
      numHands: 1,
    });
  } else {
    landmarker = await FaceLandmarker.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
      },
      runningMode,
      numFaces: 1,
    });
  }
  landmarkerMode = runningMode;
}

async function startCamera() {
  statusMsg.textContent = 'Loading AI model...';
  try {
    await ensureLandmarker('VIDEO');
  } catch (err) {
    statusMsg.textContent = 'Failed to load try-on model: ' + err.message;
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacing } });
    video.srcObject = stream;
    await video.play();
    canvas.width = video.videoWidth || 480;
    canvas.height = video.videoHeight || 360;
    switchCamBtn.classList.remove('d-none');
    statusMsg.textContent = 'Tracking... move naturally.';
    detectLoop();
  } catch (err) {
    statusMsg.textContent = 'Camera access denied or unavailable: ' + err.message;
  }
}

function stopCamera() {
  if (rafId) cancelAnimationFrame(rafId);
  rafId = null;
  if (stream) {
    stream.getTracks().forEach(t => t.stop());
    stream = null;
  }
}

startCamBtn.addEventListener('click', startCamera);
switchCamBtn.addEventListener('click', async () => {
  currentFacing = currentFacing === 'user' ? 'environment' : 'user';
  stopCamera();
  await startCamera();
});

function detectLoop() {
  if (!stream) return;
  const now = performance.now();

  // Mirror the front camera feed so it behaves like a normal selfie/mirror view
  // (left hand appears on the left, movement matches the user's own movement).
  // Only mirror for the front-facing camera; a rear camera should stay unmirrored.
  const mirror = currentFacing === 'user';

  ctx.save();
  if (mirror) {
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
  }
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  ctx.restore();

  const result = landmarker.detectForVideo(video, now);

  if (isHandMode) {
    if (result.landmarks && result.landmarks.length > 0) {
      drawRingOrBracelet(result.landmarks[0], mirror);
      statusMsg.textContent = 'Hand detected.';
    } else {
      statusMsg.textContent = 'No hand detected — show your hand to the camera.';
    }
  } else {
    if (result.faceLandmarks && result.faceLandmarks.length > 0) {
      if (config.type === 'necklace') drawNecklace(result.faceLandmarks[0], mirror);
      if (config.type === 'earring') drawEarrings(result.faceLandmarks[0], mirror);
      statusMsg.textContent = 'Face detected.';
    } else {
      statusMsg.textContent = 'No face detected — center your face in frame.';
    }
  }

  rafId = requestAnimationFrame(detectLoop);
}

function toPixel(landmark, mirror = false) {
  const x = mirror ? (1 - landmark.x) * canvas.width : landmark.x * canvas.width;
  return { x, y: landmark.y * canvas.height };
}

function drawRotatedImage(img, cx, cy, width, height, angleRad) {
  ctx.save();
  ctx.translate(cx, cy);
  ctx.rotate(angleRad);
  ctx.drawImage(img, -width / 2, -height / 2, width, height);
  ctx.restore();
}

// Tune these if the ring/bracelet still looks a bit too big or small.
// RING_SCALE is a multiple of the ring finger's knuckle-to-knuckle segment length.
const RING_SCALE = 0.95;
const BRACELET_SCALE = 2.2;

// RING: uses whichever finger is selected via the finger picker (defaults to ring finger)
// BRACELET: landmark 0 = wrist, 5 = index MCP, 17 = pinky MCP
function drawRingOrBracelet(landmarks, mirror = false) {
  if (!overlayImg.complete || overlayImg.naturalWidth === 0) return;

  if (config.type === 'ring') {
    const [mcpIdx, pipIdx] = FINGER_LANDMARKS[selectedFinger];
    const mcp = toPixel(landmarks[mcpIdx], mirror);
    const pip = toPixel(landmarks[pipIdx], mirror);
    const cx = (mcp.x + pip.x) / 2;
    const cy = (mcp.y + pip.y) / 2;
    const fingerLength = Math.hypot(pip.x - mcp.x, pip.y - mcp.y) || 1;
    const width = fingerLength * RING_SCALE;
    const height = width * (overlayImg.naturalHeight / overlayImg.naturalWidth);
    const angle = Math.atan2(pip.y - mcp.y, pip.x - mcp.x) + Math.PI / 2;
    drawRotatedImage(overlayImg, cx, cy, width, height, angle);
  } else {
    const wrist = toPixel(landmarks[0], mirror);
    const indexMcp = toPixel(landmarks[5], mirror);
    const pinkyMcp = toPixel(landmarks[17], mirror);
    const handWidth = Math.hypot(indexMcp.x - pinkyMcp.x, indexMcp.y - pinkyMcp.y) || 1;
    const width = handWidth * BRACELET_SCALE;
    const height = width * (overlayImg.naturalHeight / overlayImg.naturalWidth);
    const angle = Math.atan2(indexMcp.y - wrist.y, indexMcp.x - wrist.x) + Math.PI / 2;
    drawRotatedImage(overlayImg, wrist.x, wrist.y, width, height, angle);
  }
}

// Tune these if the necklace still looks too big/small or sits too high/low.
// NECKLACE_SCALE is a multiple of face width (234-to-454 landmark distance).
// NECKLACE_DROP is how far below the chin it's centered, also as a
// multiple of face width.
const NECKLACE_SCALE = 1.5;
const NECKLACE_DROP = 0.45;
// How much head tilt actually rotates the necklace (0 = never tilts,
// 1 = fully follows head tilt like a rigid sticker). This is 0 by default —
// a real necklace hangs straight down from gravity regardless of small head
// tilts, and the 2D face landmarks aren't reliable enough at low tilt to
// avoid "tilting the wrong way" artifacts, so keeping it level looks best.
// Raise this a little (e.g. 0.15) only if you want it to lean slightly with
// big, deliberate head tilts.
const NECKLACE_TILT_DAMPING = 0;

// NECKLACE: anchor below chin (152), scaled using face-edge landmarks 234 & 454
function drawNecklace(landmarks, mirror = false) {
  if (!overlayImg.complete || overlayImg.naturalWidth === 0) return;
  const chin = toPixel(landmarks[152], mirror);
  const leftFace = toPixel(landmarks[234], mirror);
  const rightFace = toPixel(landmarks[454], mirror);
  const faceWidth = Math.hypot(rightFace.x - leftFace.x, rightFace.y - leftFace.y) || 1;

  const width = faceWidth * NECKLACE_SCALE;
  const height = width * (overlayImg.naturalHeight / overlayImg.naturalWidth);
  // A real necklace hangs from gravity and mostly follows your shoulders,
  // not small head tilts — MediaPipe's FaceLandmarker can't see shoulders
  // (that needs a separate Pose model we're not loading here), so instead
  // we just heavily dampen how much head tilt affects the necklace's
  // rotation, which approximates the same "stays roughly level" behavior
  // without the cost of a third tracking model.
  const rawTiltAngle = Math.atan2(rightFace.y - leftFace.y, rightFace.x - leftFace.x);
  const angle = -rawTiltAngle * NECKLACE_TILT_DAMPING;
  const cx = chin.x;
  const cy = chin.y + faceWidth * NECKLACE_DROP;
  drawRotatedImage(overlayImg, cx, cy, width, height, angle);
}

// EARRINGS: approximate earlobe position near landmarks 132 (left) / 361 (right)
function drawEarrings(landmarks, mirror = false) {
  if (!overlayImg.complete || overlayImg.naturalWidth === 0) return;
  const leftFace = toPixel(landmarks[234], mirror);
  const rightFace = toPixel(landmarks[454], mirror);
  const faceWidth = Math.hypot(rightFace.x - leftFace.x, rightFace.y - leftFace.y) || 1;
  const earSize = faceWidth * 0.22;
  const earHeight = earSize * (overlayImg.naturalHeight / overlayImg.naturalWidth);

  const leftEar = toPixel(landmarks[132], mirror);
  const rightEar = toPixel(landmarks[361], mirror);

  drawRotatedImage(overlayImg, leftEar.x, leftEar.y + earHeight * 0.3, earSize, earHeight, 0);

  // mirror for right ear so the asset faces the correct way
  ctx.save();
  ctx.scale(-1, 1);
  ctx.translate(-canvas.width, 0);
  const mirroredX = canvas.width - rightEar.x;
  drawRotatedImage(overlayImg, mirroredX, rightEar.y + earHeight * 0.3, earSize, earHeight, 0);
  ctx.restore();
}

/* ---------- PHOTO UPLOAD MODE (static image, single-pass detection) ---------- */
photoInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  statusMsg.textContent = 'Processing photo...';

  try {
    await ensureLandmarker('IMAGE');
  } catch (err) {
    statusMsg.textContent = 'Failed to load try-on model: ' + err.message;
    return;
  }

  const img = new Image();
  img.onload = () => {
    canvas.width = img.width;
    canvas.height = img.height;
    ctx.drawImage(img, 0, 0);

    const result = landmarker.detect(img);
    if (isHandMode && result.landmarks && result.landmarks.length > 0) {
      drawRingOrBracelet(result.landmarks[0]);
      statusMsg.textContent = 'Hand detected in photo.';
    } else if (!isHandMode && result.faceLandmarks && result.faceLandmarks.length > 0) {
      if (config.type === 'necklace') drawNecklace(result.faceLandmarks[0]);
      if (config.type === 'earring') drawEarrings(result.faceLandmarks[0]);
      statusMsg.textContent = 'Face detected in photo.';
    } else {
      statusMsg.textContent = 'Could not detect a ' + (isHandMode ? 'hand' : 'face') + ' in this photo. Try another one with better lighting.';
    }
  };
  img.src = URL.createObjectURL(file);
});
