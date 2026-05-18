document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const filterToggle = document.getElementById("filterToggle");
    const filterPanel = document.getElementById("filterPanel");
    const filterYear = document.getElementById("filterYear");
    const filterSection = document.getElementById("filterSection");
    const filterDay = document.getElementById("filterDay");
    const filterSubject = document.getElementById("filterSubject");
    const filterDate = document.getElementById("filterDate");
    const filterStatus = document.getElementById("filterStatus");
    const pageTable = document.querySelector(".student-table[data-page]");
    const pageType = pageTable
        ? pageTable.getAttribute("data-page")
        : null;

    let tableBody = null;

    if (pageType === "assignment") {

        tableBody = document.getElementById("instructorTable");

    } else if (pageType === "attendance") {

        tableBody = document.getElementById("attendanceTable");
    }

    if (filterToggle && filterPanel) {

        filterToggle.addEventListener("click", () => {

            const isOpen =
                filterPanel.style.display === "block";

            const hasFilters = (
                (searchInput && searchInput.value.trim() !== "") ||
                (filterYear && filterYear.value.trim() !== "") ||
                (filterSection && filterSection.value.trim() !== "") ||
                (filterDay && filterDay.value.trim() !== "") ||
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

    const tableHeaders = pageTable
        ? Array.from(pageTable.querySelectorAll("thead th")).map(th =>
            th.textContent.toLowerCase().trim()
        )
        : [];

    function getHeaderIndex(name) {
        return tableHeaders.indexOf(name.toLowerCase().trim());
    }

    function getRows() {

        if (!tableBody) return [];

        return Array.from(
            tableBody.querySelectorAll("tr")
        );
    }

    function normalizeDate(dateStr) {

        if (!dateStr) return "";

        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr.trim())) {
            return dateStr.trim();
        }

        const parsed = new Date(dateStr);

        if (!isNaN(parsed.getTime())) {

            const y = parsed.getFullYear();

            const m = String(parsed.getMonth() + 1)
                .padStart(2, "0");

            const d = String(parsed.getDate())
                .padStart(2, "0");

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

        filterYear.innerHTML =
            `<option value="">All</option>`;

        allYears.forEach(year => {

            const option =
                document.createElement("option");

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

        filterSection.innerHTML =
            `<option value="">All</option>`;

        allSections.forEach(section => {

            const option =
                document.createElement("option");

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
            searchInput
            ? searchInput.value.toLowerCase().trim()
            : "";

        const yearVal =
            filterYear
            ? filterYear.value.toLowerCase().trim()
            : "";

        const sectionVal =
            filterSection
            ? filterSection.value.toLowerCase().trim()
            : "";

        const subjectVal =
            filterSubject
            ? filterSubject.value.toLowerCase().trim()
            : "";

        const dayVal =
            filterDay
            ? filterDay.value.toLowerCase().trim()
            : "";

        const dateVal =
            filterDate
            ? filterDate.value.trim()
            : "";

        const statusVal =
            filterStatus
            ? filterStatus.value.toLowerCase().trim()
            : "";

        const rows = getRows();

        rows.forEach(row => {

            if (row.cells.length === 0) return;

            let matchesSearch = true;

            let matchesYear = true;

            let matchesSection = true;

            let matchesSubject = true;

            let matchesDate = true;

            let matchesDay = true;

            let matchesStatus = true;

            if (pageType === "assignment") {

                const instructorIndex = getHeaderIndex("instructor");
                const yearIndex = getHeaderIndex("year");
                const sectionIndex = getHeaderIndex("section");
                const subjectIndex = getHeaderIndex("subject");

                const instructor =
                    row.cells[instructorIndex]?.textContent
                    .toLowerCase() || "";

                const year =
                    row.cells[yearIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                const section =
                    row.cells[sectionIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                const subject =
                    row.cells[subjectIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                matchesSearch =
                    instructor.includes(searchVal);

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

                const studentIdIndex = getHeaderIndex("student id");
                const nameIndex = getHeaderIndex("name");
                const emailIndex = getHeaderIndex("email");
                const yearIndex = getHeaderIndex("year");
                const sectionIndex = getHeaderIndex("section");
                const dayIndex = getHeaderIndex("day");
                const dateIndex = getHeaderIndex("date");
                const statusIndex = getHeaderIndex("status");

                const studentId =
                    row.cells[studentIdIndex]?.textContent
                    .toLowerCase() || "";

                const name =
                    row.cells[nameIndex]?.textContent
                    .toLowerCase() || "";

                const email =
                    row.cells[emailIndex]?.textContent
                    .toLowerCase() || "";

                const year =
                    row.cells[yearIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                const section =
                    row.cells[sectionIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                const day =
                    row.cells[dayIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

                const date =
                    row.cells[dateIndex]?.textContent
                    .trim() || "";

                const status =
                    row.cells[statusIndex]?.textContent
                    .toLowerCase()
                    .trim() || "";

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

                matchesDay =
                    dayVal === "" ||
                    day === dayVal;

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
                matchesDay &&
                matchesDate &&
                matchesStatus
            )
            ? ""
            : "none";
        });
    }

    function resetFilters() {

        if (searchInput) {
            searchInput.value = "";
        }

        if (filterYear) {
            filterYear.value = "";
        }

        if (filterSection) {
            filterSection.value = "";
        }

        if (filterDay) {
            filterDay.value = "";
        }

        if (filterSubject) {
            filterSubject.value = "";
        }

        if (filterDate) {
            filterDate.value = "";
        }

        if (filterStatus) {
            filterStatus.value = "";
        }

        populateYearOptions();

        populateSectionOptions();

        applyFilters();
    }

    if (searchInput) {
        searchInput.addEventListener(
            "input",
            applyFilters
        );
    }

    if (filterSubject) {
        filterSubject.addEventListener(
            "change",
            applyFilters
        );
    }

    if (filterYear) {
        filterYear.addEventListener(
            "change",
            applyFilters
        );
    }

    if (filterSection) {
        filterSection.addEventListener(
            "change",
            applyFilters
        );
    }

    if (filterDay) {
        filterDay.addEventListener(
            "change",
            applyFilters
        );
    }

    if (filterDate) {
        filterDate.addEventListener(
            "change",
            applyFilters
        );
    }

    if (filterStatus) {
        filterStatus.addEventListener(
            "change",
            applyFilters
        );
    }

    populateYearOptions();

    populateSectionOptions();

    applyFilters();

    document.querySelectorAll(".delete-btn")
        .forEach(btn => {

        btn.addEventListener("click", () => {

            const row = btn.closest("tr");

            const id =
                row.getAttribute("data-id");

            if (
                confirm(
                    "Are you sure you want to delete this assignment?"
                )
            ) {

                fetch(
                    "../../backend/delete_instructor.php",
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                            "application/x-www-form-urlencoded"
                        },

                        body:
                            "id=" +
                            encodeURIComponent(id)
                    }
                )

                .then(res => res.text())

                .then(res => {

                    if (
                        res.trim() === "success"
                    ) {

                        row.remove();

                        applyFilters();

                    } else {

                        alert(
                            "Failed to delete."
                        );
                    }
                })

                .catch(() => {

                    alert(
                        "Error deleting assignment."
                    );
                });
            }
        });
    });

    function convertTo24Hour(timeStr) {

        const [time, modifier] =
            timeStr.split(" ");

        if (!time || !modifier) {
            return "";
        }

        let [hours, minutes] =
            time.split(":");

        hours = parseInt(hours, 10);

        if (
            modifier === "PM" &&
            hours < 12
        ) {
            hours += 12;
        }

        if (
            modifier === "AM" &&
            hours === 12
        ) {
            hours = 0;
        }

        return `${String(hours)
            .padStart(2, "0")}:${minutes}`;
    }

});