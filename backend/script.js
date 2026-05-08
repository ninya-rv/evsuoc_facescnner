document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const filterToggle = document.getElementById("filterToggle");
    const filterPanel = document.getElementById("filterPanel");
    const filterYear = document.getElementById("filterYear");
    const filterSection = document.getElementById("filterSection");
    const filterSubject = document.getElementById("filterSubject");
    const filterDate = document.getElementById("filterDate");
    const filterStatus = document.getElementById("filterStatus");
    const pageTable = document.querySelector(".student-table[data-page]");
    const pageType = pageTable ? pageTable.getAttribute("data-page") : null;
    let tableBody = null;
    if (pageType === "assignment") {
        tableBody = document.getElementById("instructorTable");
    } else if (pageType === "attendance") {
        tableBody = document.getElementById("attendanceTable");
    }
    if (filterToggle && filterPanel) {

        filterToggle.addEventListener("click", () => {

            const isOpen = filterPanel.style.display === "block";

            const hasFilters = (
                (searchInput && searchInput.value.trim() !== "") ||
                (filterYear && filterYear.value.trim() !== "") ||
                (filterSection && filterSection.value.trim() !== "") ||
                (filterSubject && filterSubject.value.trim() !== "") ||
                (filterDate && filterDate.value.trim() !== "") ||
                (filterStatus && filterStatus.value.trim() !== "")
            );

            if (!isOpen) {

                filterPanel.style.display = "block";

                filterToggle.innerHTML =
                    '<i class="fa-solid fa-arrows-rotate"></i>';

                populateYearOptions();
                populateSectionOptions();

                applyFilters();

            } else if (hasFilters) {

                resetFilters();

            } else {

                filterPanel.style.display = "none";

                filterToggle.innerHTML =
                    '<i class="fa-solid fa-filter"></i>';
            }
        });
    }
    function getRows() {

        if (!tableBody) return [];

        return Array.from(tableBody.querySelectorAll("tr"));
    }
    function normalizeDate(dateStr) {
        if (!dateStr) return "";
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr.trim())) {
            return dateStr.trim();
        }
        const parsed = new Date(dateStr);
        if (!isNaN(parsed.getTime())) {
            const y = parsed.getFullYear();
            const m = String(parsed.getMonth() + 1).padStart(2, "0");
            const d = String(parsed.getDate()).padStart(2, "0");

            return `${y}-${m}-${d}`;
        }
        return dateStr.trim();
    }
    function populateYearOptions() {
        if (!filterYear) return;
        const currentValue = filterYear.value;
        const allYears = [
            "1st Year",
            "2nd Year",
            "3rd Year",
            "4th Year"
        ];
        filterYear.innerHTML = `<option value="">All</option>`;

        allYears.forEach(year => {

            const option = document.createElement("option");

            option.value = year;
            option.textContent = year;

            filterYear.appendChild(option);
        });
        if (allYears.includes(currentValue)) {
            filterYear.value = currentValue;
        } else {
            filterYear.value = "";
        }
    }
    function populateSectionOptions() {
        if (!filterSection) return;
        const currentValue = filterSection.value;
        const allSections = ["A", "B", "C", "D"];
        filterSection.innerHTML = `<option value="">All</option>`;
        allSections.forEach(section => {
            const option = document.createElement("option");
            option.value = section;
            option.textContent = section;
            filterSection.appendChild(option);
        });
        if (allSections.includes(currentValue)) {
            filterSection.value = currentValue;
        } else {
            filterSection.value = "";
        }
    }
    function applyFilters() {
        if (!tableBody) return;
        const searchVal =
            searchInput ? searchInput.value.toLowerCase().trim() : "";
        const yearVal =
            filterYear ? filterYear.value.toLowerCase().trim() : "";
        const sectionVal =
            filterSection ? filterSection.value.toLowerCase().trim() : "";
        const subjectVal =
            filterSubject ? filterSubject.value.toLowerCase().trim() : "";
        const dateVal =
            filterDate ? filterDate.value.trim() : "";
        const statusVal =
            filterStatus ? filterStatus.value.toLowerCase().trim() : "";
        const rows = getRows();
        rows.forEach(row => {
            if (row.cells.length === 0) return;
            let matchesSearch = true;
            let matchesYear = true;
            let matchesSection = true;
            let matchesSubject = true;
            let matchesDate = true;
            let matchesStatus = true;
            if (pageType === "assignment") {

                const instructor =
                    row.cells[0]?.textContent.toLowerCase() || "";

                const year =
                    row.cells[1]?.textContent.toLowerCase().trim() || "";

                const section =
                    row.cells[2]?.textContent.toLowerCase().trim() || "";

                const subject =
                    row.cells[3]?.textContent.toLowerCase().trim() || "";

                matchesSearch = instructor.includes(searchVal);

                matchesYear =
                    yearVal === "" ||
                    year === yearVal;

                matchesSection =
                    sectionVal === "" ||
                    section === sectionVal;

                matchesSubject =
                    subjectVal === "" ||
                    subject === subjectVal;
            }
            if (pageType === "attendance") {

                const studentId =
                    row.cells[0]?.textContent.toLowerCase() || "";

                const name =
                    row.cells[1]?.textContent.toLowerCase() || "";

                const email =
                    row.cells[2]?.textContent.toLowerCase() || "";

                const subject =
                    row.cells[3]?.textContent.toLowerCase().trim() || "";

                const year =
                    row.cells[4]?.textContent.toLowerCase().trim() || "";

                const section =
                    row.cells[5]?.textContent.toLowerCase().trim() || "";

                const date =
                    row.cells[6]?.textContent.trim() || "";

                const status =
                    row.cells[9]?.textContent.toLowerCase().trim() || "";

                matchesSearch =
                    studentId.includes(searchVal) ||
                    name.includes(searchVal) ||
                    email.includes(searchVal);

                matchesYear =
                    yearVal === "" ||
                    year === yearVal;

                matchesSection =
                    sectionVal === "" ||
                    section === sectionVal;

                matchesSubject =
                    subjectVal === "" ||
                    subject === subjectVal;

                matchesDate =
                    dateVal === "" ||
                    normalizeDate(date) === dateVal;

                matchesStatus =
                    statusVal === "" ||
                    status === statusVal;
            }
            row.style.display = (
                matchesSearch &&
                matchesYear &&
                matchesSection &&
                matchesSubject &&
                matchesDate &&
                matchesStatus
            ) ? "" : "none";
        });
    }
    function resetFilters() {

        if (searchInput) searchInput.value = "";
        if (filterYear) filterYear.value = "";
        if (filterSection) filterSection.value = "";
        if (filterSubject) filterSubject.value = "";
        if (filterDate) filterDate.value = "";
        if (filterStatus) filterStatus.value = "";

        populateYearOptions();
        populateSectionOptions();

        applyFilters();
    }
    if (searchInput) {
        searchInput.addEventListener("input", applyFilters);
    }
    if (filterSubject) {
        filterSubject.addEventListener("change", applyFilters);
    }
    if (filterYear) {
        filterYear.addEventListener("change", applyFilters);
    }
    if (filterSection) {
        filterSection.addEventListener("change", applyFilters);
    }
    if (filterDate) {
        filterDate.addEventListener("change", applyFilters);
    }
    if (filterStatus) {
        filterStatus.addEventListener("change", applyFilters);
    }

    populateYearOptions();
    populateSectionOptions();
    applyFilters();
    document.querySelectorAll(".delete-btn").forEach(btn => {

        btn.addEventListener("click", () => {

            const row = btn.closest("tr");
            const id = row.getAttribute("data-id");

            if (confirm("Are you sure you want to delete this assignment?")) {

                fetch("../../backend/delete_instructor.php", {
                    method: "POST",
                    headers: {
                        "Content-Type":
                        "application/x-www-form-urlencoded"
                    },
                    body: "id=" + encodeURIComponent(id)
                })

                .then(res => res.text())

                .then(res => {

                    if (res.trim() === "success") {

                        row.remove();

                        applyFilters();

                    } else {

                        alert("Failed to delete.");
                    }
                })

                .catch(() => {

                    alert("Error deleting assignment.");
                });
            }
        });
    });
    function convertTo24Hour(timeStr) {

        const [time, modifier] = timeStr.split(" ");

        if (!time || !modifier) return "";

        let [hours, minutes] = time.split(":");

        hours = parseInt(hours, 10);

        if (modifier === "PM" && hours < 12) {
            hours += 12;
        }

        if (modifier === "AM" && hours === 12) {
            hours = 0;
        }

        return `${String(hours).padStart(2, "0")}:${minutes}`;
    }
});