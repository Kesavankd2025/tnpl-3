/* Product specification PDF download for product detail pages */
(function () {
    function sanitizeFileName(value) {
        return (value || "product")
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "");
    }

    function getProductTitle() {
        var titleEl = document.querySelector(".detail-content h2") || document.querySelector("h1");
        return titleEl ? titleEl.textContent.trim() : "Product";
    }

    function getSpecRows() {
        var rows = [];
        var specItems = document.querySelectorAll(".spec-grid .spec-item");
        specItems.forEach(function (item) {
            var labelEl = item.querySelector(".spec-label");
            var valueEl = item.querySelector(".spec-value");
            var label = labelEl ? labelEl.textContent.trim() : "";
            var value = valueEl ? valueEl.textContent.trim() : "";
            if (label && value) {
                rows.push([label, value]);
            }
        });
        return rows;
    }

    function downloadSpecPdf(event) {
        event.preventDefault();

        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert("PDF library failed to load. Please refresh and try again.");
            return;
        }

        var title = getProductTitle();
        var rows = getSpecRows();
        if (!rows.length) {
            alert("No specifications found on this page.");
            return;
        }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF();
        var pageHeight = doc.internal.pageSize.getHeight();
        var y = 20;

        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.text("Aruna & Co.", 14, y);
        y += 10;

        doc.setFontSize(14);
        doc.text(title + " - Key Specifications", 14, y);
        y += 8;

        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        doc.text("Generated on: " + new Date().toLocaleDateString(), 14, y);
        y += 10;

        doc.setDrawColor(220, 220, 220);
        doc.line(14, y, 196, y);
        y += 8;

        doc.setFontSize(11);
        rows.forEach(function (row) {
            var label = row[0];
            var value = row[1];

            if (y > pageHeight - 20) {
                doc.addPage();
                y = 20;
            }

            doc.setFont("helvetica", "bold");
            doc.text(label + ":", 14, y);
            y += 6;

            doc.setFont("helvetica", "normal");
            var wrapped = doc.splitTextToSize(value, 178);
            doc.text(wrapped, 18, y);
            y += (wrapped.length * 6) + 2;
        });

        doc.save(sanitizeFileName(title) + "-key-specifications.pdf");
    }

    document.addEventListener("DOMContentLoaded", function () {
        var button = document.querySelector(".spec-download-btn");
        if (!button) {
            return;
        }
        button.addEventListener("click", downloadSpecPdf);
    });
})();
