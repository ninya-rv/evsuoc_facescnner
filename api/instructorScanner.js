document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("video");
    const statusDiv = document.getElementById("status");
    const warningBox = document.getElementById("warning");
    const classSelect = document.getElementById("classSelect");
    const timeInBtn = document.getElementById("timeInBtn");
    const timeOutBtn = document.getElementById("timeOutBtn");
    const step1Indicator = document.getElementById("step1Indicator");
    const step2Indicator = document.getElementById("step2Indicator");
    const profileBtn = document.getElementById("profileBtn");
    const dropdown = document.getElementById("profileDropdown");

    let selectedClass = null;
    let scanMode = null;
    let faceMatcher;
    let modelsLoaded = false;
    let scannerInitialized = false;
    let recentScans = {};

    if (classSelect) {
        classSelect.disabled = true;
    }

    function showWarning(message) {
        if (!warningBox) return;
        warningBox.innerText = message || "";
        warningBox.style.display = message ? "block" : "none";
    }

    function setModeButtons(mode) {
        if (!timeInBtn || !timeOutBtn) return;
        timeInBtn.classList.toggle("active", mode === "time_in");
        timeOutBtn.classList.toggle("active", mode === "time_out");
    }

    function goToStep2() {
        if (!step1Indicator || !step2Indicator) return;
        step1Indicator.classList.remove("active");
        step2Indicator.classList.add("active");
        step1Indicator.style.background = "#ddd";
        step1Indicator.style.color = "#333";
        step2Indicator.style.background = "#8B0000";
        step2Indicator.style.color = "#fff";
    }

    function prepareScanner() {
        const scannerArea = document.getElementById("scannerArea");
        if (scannerArea) {
            scannerArea.style.display = "flex";
        }
        if (statusDiv && selectedClass) {
            statusDiv.innerText =
                "Scanner ready (" + scanMode.replace("_", " ") + ") - " + selectedClass.subject;
        }
        showWarning("");
        goToStep2();

        if (!scannerInitialized) {
            initScanner();
        }
    }

    if (timeInBtn) {
        timeInBtn.addEventListener("click", () => {
            scanMode = "time_in";
            setModeButtons(scanMode);
            if (statusDiv) statusDiv.innerText = "Mode: Time In selected";
            showWarning("");
            if (classSelect) classSelect.disabled = false;
        });
    }

    if (timeOutBtn) {
        timeOutBtn.addEventListener("click", () => {
            scanMode = "time_out";
            setModeButtons(scanMode);
            if (statusDiv) statusDiv.innerText = "Mode: Time Out selected";
            showWarning("");
            if (classSelect) classSelect.disabled = false;
        });
    }

    function getPHTime() {
        const now = new Date();
        return new Date(now.toLocaleString("en-US", { timeZone: "Asia/Manila" }));
    }

    if (classSelect) {
        classSelect.addEventListener("change", () => {
            const option = classSelect.options[classSelect.selectedIndex];
            if (!option || !option.value) return;

            const phNow = getPHTime();
            const endTime = option.dataset.end;
            const today = phNow.toISOString().split("T")[0];
            const classEnd = new Date(`${today}T${endTime}`);

            if (phNow > classEnd) {
                showWarning("❌ This class already ended. You cannot select it.");
                classSelect.value = "";
                return;
            }

            if (scanMode) {
                selectedClass = {
                    assignment_id: option.value,
                    year_level: option.dataset.year,
                    section: option.dataset.section,
                    subject: option.dataset.subject,
                    start_time: option.dataset.start,
                    end_time: option.dataset.end,
                    mode: scanMode
                };
                prepareScanner();
            }
        });
    }

    async function loadModels() {
        if (modelsLoaded) return;
        if (statusDiv) statusDiv.innerText = "Loading face models...";
        await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('models');
        modelsLoaded = true;
    }

    async function startCamera() {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
        return new Promise(resolve => {
            video.onloadedmetadata = () => resolve();
        });
    }

    async function loadStudentFaces() {
        if (statusDiv) statusDiv.innerText = "Loading registered students...";
        const res = await fetch("/backend/get_student.php");
        const students = await res.json();
        const labeledDescriptors = students.map(student => {
            const label = `${student.name} ${student.student_id}`;
            const descriptor = Array.isArray(student.face_descriptor)
                ? new Float32Array(student.face_descriptor)
                : null;
            if (!descriptor) {
                console.warn("Skipping student without valid descriptor:", label);
                return null;
            }
            return new faceapi.LabeledFaceDescriptors(label, [descriptor]);
        }).filter(Boolean);

        if (labeledDescriptors.length === 0) {
            showWarning("⚠️ No student faces found in the database!");
            if (statusDiv) statusDiv.innerText = "No student faces loaded";
            return;
        }
        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
    }

    async function getStudentByLabel(label) {
        try {
            const res = await fetch("/backend/get_student.php");
            const students = await res.json();
            return students.find(student => {
                const studentLabel = `${student.name} ${student.student_id}`;
                return studentLabel === label;
            }) || null;
        } catch (error) {
            console.error("Error finding student by label:", error);
            return null;
        }
    }

    async function saveAttendance(studentData) {
        if (!selectedClass) {
            console.warn("No class selected yet.");
            showWarning("⚠️ Please select class first.");
            return;
        }
        try {
            const response = await fetch("/backend/save_attendance.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    student_id: studentData.student_id,
                    name: studentData.name,
                    email: studentData.email,
                    subject: selectedClass.subject,
                    year_level: selectedClass.year_level,
                    section: selectedClass.section,
                    assignment_id: selectedClass.assignment_id,
                    start_time: selectedClass.start_time,
                    end_time: selectedClass.end_time,
                    mode: selectedClass.mode
                })
            });
            const result = await response.json();
            console.log("Attendance save result:", result);
            if (result.success) {
                if (statusDiv) statusDiv.innerText = `✅ ${studentData.name} - ${result.message}`;
            } else {
                if (statusDiv) statusDiv.innerText = `⚠️ ${result.message}`;
            }
        } catch (error) {
            console.error("Error saving attendance:", error);
            if (statusDiv) statusDiv.innerText = "❌ Failed to save attendance";
        }
    }

    function startRecognition() {
        if (statusDiv) statusDiv.innerText = "Scanner ready.";
        setInterval(async () => {
            const detections = await faceapi
                .detectAllFaces(video)
                .withFaceLandmarks()
                .withFaceDescriptors();
            if (detections.length === 0) {
                if (statusDiv) statusDiv.innerText = "No face detected";
                return;
            }
            for (const detection of detections) {
                const match = faceMatcher.findBestMatch(detection.descriptor);
                if (match.label !== "unknown") {
                    if (statusDiv) statusDiv.innerText = `✅ ${match.label}`;
                    const now = Date.now();
                    if (!recentScans[match.label] || (now - recentScans[match.label] > 10000)) {
                        recentScans[match.label] = now;
                        const student = await getStudentByLabel(match.label);
                        if (student) {
                            await saveAttendance(student);
                        } else {
                            console.warn("Matched student not found in get_student.php");
                        }
                    }
                } else {
                    if (statusDiv) statusDiv.innerText = "❌ Unknown face detected";
                }
            }
        }, 1000);
    }

    async function initScanner() {
        if (scannerInitialized) return;
        await loadModels();
        await startCamera();
        await loadStudentFaces();
        if (faceMatcher) {
            startRecognition();
        }
        scannerInitialized = true;
    }

    if (profileBtn) {
        profileBtn.addEventListener("click", () => {
            if (!dropdown) return;
            dropdown.style.display =
                dropdown.style.display === "block" ? "none" : "block";
        });
    }

    document.addEventListener("click", function (e) {
        if (!profileBtn || !dropdown) return;
        if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
});

