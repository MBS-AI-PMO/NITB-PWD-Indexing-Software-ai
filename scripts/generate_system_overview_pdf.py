#!/usr/bin/env python3
"""Generate PWD DMS system overview PDF with Deep Search & AI section."""

from fpdf import FPDF
from fpdf.enums import XPos, YPos
from pathlib import Path

OUTPUT = Path(__file__).resolve().parent.parent / "PWD_DMS_System_Overview.pdf"


class OverviewPDF(FPDF):
    def header(self):
        self.set_fill_color(30, 64, 175)
        self.rect(0, 0, 210, 20, "F")
        self.set_text_color(255, 255, 255)
        self.set_font("Helvetica", "B", 15)
        self.set_xy(10, 5)
        self.cell(0, 8, "PWD DMS - System Overview", new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_font("Helvetica", "", 7.5)
        self.set_xy(10, 13)
        self.cell(0, 4, "Public Works Department Document Management System")

    def footer(self):
        self.set_y(-10)
        self.set_font("Helvetica", "I", 7)
        self.set_text_color(120, 120, 120)
        self.cell(0, 5, f"Page {self.page_no()}", align="C")

    def section(self, title):
        self.ln(2)
        self.set_text_color(30, 64, 175)
        self.set_font("Helvetica", "B", 9)
        self.cell(0, 4.5, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(40, 40, 40)
        self.set_font("Helvetica", "", 7.8)
        self.ln(0.5)

    def bullet(self, text):
        self.set_x(11)
        self.multi_cell(188, 3.6, f"- {text}")
        self.set_x(self.l_margin)

    def para(self, text):
        self.set_x(self.l_margin)
        self.multi_cell(0, 3.6, text)
        self.ln(0.5)


def build_pdf():
    pdf = OverviewPDF("P", "mm", "A4")
    pdf.set_auto_page_break(auto=True, margin=14)
    pdf.set_margins(10, 24, 10)
    pdf.add_page()

    pdf.section("WHAT IS THIS SYSTEM?")
    pdf.para(
        "PWD DMS is a web-based Document Management System for the Public Works Department. "
        "It stores official records (PDF files and images) across organizational Wings, with "
        "folders, retention categories, metadata fields, and intelligent search - so users can "
        "find documents not only by title or file number, but also by words written inside the "
        "actual document."
    )

    pdf.section("DEEP SEARCH - HOW PDF & IMAGE SEARCH WORKS")
    pdf.para(
        "Normal search only looks at form fields (subject title, file number). Deep Search goes "
        "inside the uploaded file itself. When a PDF or image is uploaded, the system automatically "
        "reads the document in the background using AI-powered document intelligence and saves "
        "all readable text into the database. Later, when you type a keyword in the search box, "
        "the system searches three layers at once:"
    )
    pdf.bullet("Layer 1 - Record metadata: subject title and official file number (e.g. PWD/2025/...)")
    pdf.bullet("Layer 2 - Deep content: full text read from inside the PDF or image file")
    pdf.bullet("Layer 3 - Combined filters: category, classification, date range, folder, wing, etc.")
    pdf.para(
        "Example: If a scanned register page contains a name or number that was never typed in the "
        "upload form, Deep Search can still find that document - because the AI already read and "
        "stored that text when the file was processed."
    )

    pdf.section("AI DOCUMENT READING - WHAT HAPPENS AFTER UPLOAD")
    pdf.para(
        "Upload is instant for the user. AI processing runs silently in the background:"
    )
    pdf.bullet("Step 1: File is saved securely (PDF, JPG, PNG, or WEBP up to 50MB)")
    pdf.bullet("Step 2: AI reads every page - printed text, scanned pages, and image photos")
    pdf.bullet("Step 3: Extracted text (Urdu, English, Roman Urdu, numbers) is stored in the database")
    pdf.bullet("Step 4: Document becomes fully searchable in Master Register within minutes")
    pdf.para(
        "For PDFs: AI converts scanned/non-selectable PDFs into searchable documents and reads "
        "all page content. For images (phone photos, WhatsApp scans, handwritten registers): AI "
        "analyses the image visually and extracts visible text. Handwritten or rotated documents "
        "may take longer to process but are still indexed for search once complete."
    )

    pdf.section("USER ROLES & MAIN MODULES")
    pdf.bullet("Admin: all Wings, dashboard, impersonate wing users, system-wide Deep Search")
    pdf.bullet("Wing User: own wing documents, folders, categories, and search")
    pdf.bullet("Dashboard | All Wings | Document List | Folder Manager | Category Index")

    pdf.section("HOW TO USE")
    steps = [
        "Login at /login  (default admin: info@pwd.com / admin123 after db:seed)",
        "Admin creates Wings; each wing gets its own user account",
        "Upload document with metadata (category, file no, subject, classification)",
        "Wait 1-5 min for AI background processing to finish",
        "Use search box - type any word from inside the document, not just the title",
        "Combine search with filters (category, dates, folder) for precise results",
    ]
    for i, step in enumerate(steps, 1):
        pdf.bullet(f"{i}. {step}")

    pdf.ln(1)
    pdf.set_fill_color(239, 246, 255)
    pdf.set_text_color(30, 58, 138)
    pdf.set_font("Helvetica", "B", 7.5)
    pdf.multi_cell(
        0,
        3.5,
        "Deep Search Tip: If a newly uploaded document does not appear in content search yet, "
        "AI processing may still be running. Refresh after a few minutes or use Re-extract to "
        "re-process the file manually.",
        fill=True,
    )

    pdf.output(str(OUTPUT))
    print(f"PDF created: {OUTPUT}")


if __name__ == "__main__":
    build_pdf()
