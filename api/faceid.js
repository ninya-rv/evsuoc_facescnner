function isLocalhost() {
  return window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
}

function showAlert(message) {
  if (!isLocalhost()) {
    alert(message);
  }
}

function showWarning(message) {
  const warningDiv = document.getElementById("warning");
  if (warningDiv && !isLocalhost()) {
    warningDiv.innerHTML = message;
  }
}

const video = document.getElementById('video');
const statusDiv = document.getElementById('status');
const warningDiv = document.getElementById('warning');

let modelsLoaded = false;

async function loadModels() {

    if (modelsLoaded) return;

    statusDiv.innerText = "Loading face models...";

    await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('models');

    modelsLoaded = true;

    statusDiv.innerText = "Ready to register face.";
}


async function startCamera() {

    const stream = await navigator.mediaDevices.getUserMedia({ video: true });

    video.srcObject = stream;
    video.style.display = "block";

    return new Promise(resolve => {
        video.onloadedmetadata = () => {
            resolve();
        };
    });
}


async function registerFace() {

    warningDiv.innerText="";

    const student_id = document.getElementById('student_id').value.trim();
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const year = document.getElementById('year').value;
    const section = document.getElementById('section').value;

    if (!student_id || !name || !email || !year || !section) {
        showWarning("⚠️ Please fill all fields first.");
        return;
    }

    const agree = document.getElementById("agree_terms");

    if(!agree.checked){
        showWarning("⚠️ You must agree to the Terms & Conditions before registering your face.");
        return;
    }

    try {

        await loadModels();
        await startCamera();

        statusDiv.innerText = "Look at the camera";

        await new Promise(r => setTimeout(r, 1000));

        const detection = await faceapi
            .detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {

            statusDiv.innerText = "❌ No face detected.";
            showAlert("❌ No face detected. Please try again.");

            return;
        }

        const descriptor = Array.from(detection.descriptor);

        statusDiv.innerText = "Uploading face data...";

        const response = await fetch("/backend/upload_face.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                student_id,
                name,
                email,
                year,
                section,
                descriptor
            })

        });

        const result = await response.json();

        if (result.success) {

            statusDiv.innerText = "✅ Student Registered Successfully";
            showAlert("✅ Face registered and saved to database!");

            document.getElementById("registrationForm").reset();

        } else if (result.type === "duplicate_id") {

            statusDiv.innerText = "⚠️ Student ID already registered!";
            showAlert("⚠️ This Student ID is already registered.");

        } else if (result.type === "duplicate_face") {

            statusDiv.innerText = "⚠️ This face is already registered!";
            showAlert("⚠️ This face already exists in the system.");

        } else {

            statusDiv.innerText = "❌ " + result.msg;
            showAlert("❌ Registration failed: " + result.msg);

        }

    } catch (error) {

        console.error(error);

        statusDiv.innerText = "❌ System error.";
        showAlert("❌ System error occurred.");

    }
}


function showTerms(){
    document.getElementById("termsModal").style.display="block";
}

function closeTerms(){
    document.getElementById("termsModal").style.display="none";
}


function nextStep() {
    const studentId = document.getElementById("student_id").value.trim();
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const year = document.getElementById("year").value;
    const section = document.getElementById("section").value;
    const agreeTerms = document.getElementById("agree_terms").checked;
    const warningDiv = document.getElementById("warning");

    warningDiv.innerHTML = "";

    if (!studentId) {
      showWarning("Please enter Student ID");
      return;
    }
    if (!name) {
      showWarning("Please enter Name");
      return;
    }
    if (!email) {
      showWarning("Please enter Email");
      return;
    }
    if (!year) {
      showWarning("Please select Year");
      return;
    }
    if (!section) {
      showWarning("Please select Section");
      return;
    }
    if (!agreeTerms) {
      showWarning("Please agree to Terms & Conditions");
      return;
    }

    document.getElementById("step1-content").classList.remove("active");
    document.getElementById("step2-content").classList.add("active");
    document.getElementById("step1-indicator").classList.remove("active");
    document.getElementById("step2-indicator").classList.add("active");

    startVideoStream();
}

function previousStep() {
    document.getElementById("step2-content").classList.remove("active");
    document.getElementById("step1-content").classList.add("active");
    document.getElementById("step2-indicator").classList.remove("active");
    document.getElementById("step1-indicator").classList.add("active");

    stopVideoStream();
}

function startVideoStream() {
    navigator.mediaDevices.getUserMedia({ video: {} })
      .then(stream => {
        const video = document.getElementById("video");
        video.srcObject = stream;
        video.style.display = "block";
      })
      .catch(err => {
        showWarning("Camera access denied: " + err.message);
      });
}

function stopVideoStream() {
    const video = document.getElementById("video");
    if (video.srcObject) {
      const tracks = video.srcObject.getTracks();
      tracks.forEach(track => track.stop());
      video.srcObject = null;
      video.style.display = "none";
    }
}


document.addEventListener("DOMContentLoaded", function() {
    const agreeTermsCheckbox = document.getElementById("agree_terms");
    if (agreeTermsCheckbox) {
        agreeTermsCheckbox.addEventListener("change", function(){
            const warningDiv = document.getElementById("warning");
            if (warningDiv) {
                warningDiv.innerText = "";
            }
        });
    }
});