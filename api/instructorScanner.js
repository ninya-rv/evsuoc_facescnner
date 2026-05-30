document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById("video");
    const statusDiv = document.getElementById("status");
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
        classSelect.disabled = false;
    }

    function checkAndAutoSelectTimeIn() {

        if (!classSelect) return;

        const now = getPHTime();

        let matchedOption = null;
        let detectedMode = null;

        const options = Array.from(classSelect.options);

        options.forEach(option => {

            if (!option.value) return;
            if (option.disabled) return;

            const today = [
                now.getFullYear(),
                String(now.getMonth() + 1).padStart(2, "0"),
                String(now.getDate()).padStart(2, "0")
            ].join("-");

            const startTime = new Date(`${today}T${option.dataset.start}`);
            const endTime = new Date(`${today}T${option.dataset.end}`);

            // 30 minutes before class end
            const timeoutTrigger = new Date(
                endTime.getTime() - (30 * 60 * 1000)
            );

            const nowTime = now.getTime();
            const start = startTime.getTime();
            const end = endTime.getTime();
            const timeout = timeoutTrigger.getTime();

            if (nowTime >= start && nowTime < timeout) {

                matchedOption = option;
                detectedMode = "time_in";
            }

            else if (nowTime >= timeout && nowTime <= end) {

                matchedOption = option;
                detectedMode = "time_out";
            }
        });

        if (matchedOption && detectedMode) {

            classSelect.disabled = false;

            classSelect.value = matchedOption.value;

            selectedClass = {
                assignment_id: matchedOption.value,
                year_level: matchedOption.dataset.year,
                section: matchedOption.dataset.section,
                subject: matchedOption.dataset.subject,
                day: matchedOption.dataset.day,
                start_time: matchedOption.dataset.start,
                end_time: matchedOption.dataset.end,
                mode: detectedMode
            };

            scanMode = detectedMode;

            setModeButtons(scanMode);

            prepareScanner();

            setStatus(
                `Scanner ready (${scanMode.replace("_", " ")}) - ${selectedClass.subject}`
            );
        }
    }
    function setStatus(message) {
        if (!statusDiv) return;
        statusDiv.innerText = message;
    }

    function setModeButtons(mode) {
        timeInBtn?.classList.toggle("active", mode === "time_in");
        timeOutBtn?.classList.toggle("active", mode === "time_out");
    }

    function goToStep2() {
        step1Indicator?.classList.remove("active");
        step2Indicator?.classList.add("active");

        step1Indicator.style.background = "#ddd";
        step1Indicator.style.color = "#333";

        step2Indicator.style.background = "#8B0000";
        step2Indicator.style.color = "#fff";
    }

    function prepareScanner() {
        document.getElementById("scannerArea").style.display = "flex";

        setStatus(
            `Scanner ready (${scanMode.replace("_", " ")}) - ${selectedClass.subject}`
        );

        goToStep2();

        if (!scannerInitialized) initScanner();
    }

    timeInBtn?.addEventListener("click", () => {

        scanMode = "time_in";

        setModeButtons(scanMode);

        setStatus("Mode: Time In selected");

        classSelect.disabled = false;

        if (classSelect.value) {
            classSelect.dispatchEvent(new Event("change"));
        }
    });

    timeOutBtn?.addEventListener("click", () => {

        scanMode = "time_out";

        setModeButtons(scanMode);

        setStatus("Mode: Time Out selected");

        classSelect.disabled = false;

        if (classSelect.value) {
            classSelect.dispatchEvent(new Event("change"));
        }
    });
    function getPHTime() {
        const now = new Date();
        return new Date(now.toLocaleString("en-US", { timeZone: "Asia/Manila" }));
    }

    classSelect?.addEventListener("change", () => {
        const option = classSelect.options[classSelect.selectedIndex];
        if (!option?.value) return;

        const phNow = getPHTime();
        const today = phNow.toISOString().split("T")[0];
        const classEnd = new Date(`${today}T${option.dataset.end}`);

        if (phNow > classEnd) {
            setStatus("❌ Class already ended");
            classSelect.value = "";
            return;
        }

        selectedClass = {
            assignment_id: option.value,
            year_level: option.dataset.year,
            section: option.dataset.section,
            subject: option.dataset.subject,
            day: option.dataset.day,
            start_time: option.dataset.start,
            end_time: option.dataset.end,
            mode: scanMode
        };

        prepareScanner();
    });

    async function loadModels() {
        if (modelsLoaded) return;

        setStatus("Loading models...");

        await faceapi.nets.ssdMobilenetv1.loadFromUri("models");
        await faceapi.nets.faceLandmark68Net.loadFromUri("models");
        await faceapi.nets.faceRecognitionNet.loadFromUri("models");

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
        setStatus("Loading students...");

        const res = await fetch("/backend/get_student.php");
        const response = await res.json();

        const students = response.students || [];

        if (!Array.isArray(students) || students.length === 0) {
            setStatus("No students found");
            faceMatcher = null;
            return;
        }

        const labeled = students.map(s => {
            const descriptor = Array.isArray(s.face_descriptor)
                ? new Float32Array(s.face_descriptor)
                : null;

            if (!descriptor) return null;

            return new faceapi.LabeledFaceDescriptors(
                `${s.first_name} ${s.last_name} ${s.student_id}`,
                [descriptor]
            );
        }).filter(Boolean);

        if (!labeled.length) {
            setStatus("No valid face data");
            faceMatcher = null;
            return;
        }

        faceMatcher = new faceapi.FaceMatcher(labeled, 0.6);
        setStatus("Students loaded successfully");
    }

    async function getStudentByLabel(label) {
        const res = await fetch("/backend/get_student.php");
        const response = await res.json();
        const students = response.students || [];

        return students.find(
            s => `${s.first_name} ${s.last_name} ${s.student_id}` === label
        );
    }

    async function saveAttendance(student) {
        if (!selectedClass) return;

        const res = await fetch("/backend/save_attendance.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                student_id: student.student_id,
                first_name: student.first_name,
                last_name: student.last_name,
                email: student.email,
                subject: selectedClass.subject,
                year_level: selectedClass.year_level,
                section: selectedClass.section,
                day: selectedClass.day,
                start_time: selectedClass.start_time,
                end_time: selectedClass.end_time,
                mode: selectedClass.mode
            })
        });

        const result = await res.json();

        if (result.success) {
            setStatus("✅ Successfully saved attendance");
            return;
        }

        if (result.type === "inactive") {
            setStatus("❌ Student is inactive");
            return;
        }

        if (result.type === "not_assigned") {
            setStatus("❌ Not assigned to this class");
            return;
        }

        setStatus("❌ " + result.message);
    }

    function startRecognition() {
        setStatus("Scanner ready...");

        setInterval(async () => {
            const detections = await faceapi
                .detectAllFaces(video)
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (!detections.length) {
                setStatus("No face detected");
                return;
            }

            for (const d of detections) {
                const match = faceMatcher.findBestMatch(d.descriptor);

                if (match.label !== "unknown") {
                    const now = Date.now();

                    if (!recentScans[match.label] || now - recentScans[match.label] > 10000) {
                        recentScans[match.label] = now;

                        const student = await getStudentByLabel(match.label);
                        if (student) await saveAttendance(student);
                    }
                } else {
                    setStatus("❌ Unknown face");
                }
            }
        }, 1000);
    }

    async function initScanner() {
        if (scannerInitialized) return;

        await loadModels();
        await startCamera();
        await loadStudentFaces();

        if (faceMatcher) startRecognition();

        scannerInitialized = true;
    }

    profileBtn?.addEventListener("click", () => {
        dropdown.style.display =
            dropdown.style.display === "block" ? "none" : "block";
    });

    document.addEventListener("click", (e) => {
        if (!profileBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });

    // Auto-select Time In if there's an active schedule
    checkAndAutoSelectTimeIn();
});