import os
from reportlab.lib.pagesizes import letter
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors

def build_pdf(filename):
    doc = SimpleDocTemplate(
        filename,
        pagesize=letter,
        rightMargin=36,
        leftMargin=36,
        topMargin=36,
        bottomMargin=36
    )

    styles = getSampleStyleSheet()

    # Custom styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Heading1'],
        fontSize=20,
        leading=24,
        textColor=colors.HexColor('#2c221e'),
        spaceAfter=4
    )

    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontSize=10,
        leading=14,
        textColor=colors.HexColor('#d97724'),
        spaceAfter=0
    )

    h1_style = ParagraphStyle(
        'H1',
        parent=styles['Heading2'],
        fontSize=13,
        leading=17,
        textColor=colors.HexColor('#8c4a27'),
        spaceBefore=12,
        spaceAfter=6
    )

    body_style = ParagraphStyle(
        'Body',
        parent=styles['Normal'],
        fontSize=9.5,
        leading=13.5,
        textColor=colors.HexColor('#333333'),
        spaceAfter=5
    )

    bullet_style = ParagraphStyle(
        'Bullet',
        parent=body_style,
        leftIndent=15,
        spaceAfter=4
    )

    story = []

    # Header with Logo Table
    logo_path = r"c:\xampp\htdocs\bakery manegement\assets\images\mlb_logo.jpg"
    if os.path.exists(logo_path):
        img_logo = Image(logo_path, width=65, height=65)
        header_text = [
            Paragraph("Project Proposal: MLB POS System Expansion", title_style),
            Paragraph("Phase 2 Technical Proposal & Commercial Investment Breakdown", subtitle_style)
        ]
        header_table = Table([[img_logo, header_text]], colWidths=[75, 465])
        header_table.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('PADDING', (0,0), (-1,-1), 0),
        ]))
        story.append(header_table)
    else:
        story.append(Paragraph("Project Proposal: MLB POS System Expansion", title_style))
        story.append(Paragraph("Phase 2 Technical Proposal & Commercial Investment Breakdown", subtitle_style))

    story.append(Spacer(1, 10))
    story.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor('#d97724'), spaceAfter=12))

    # Meta Table
    meta_data = [
        [Paragraph("<b>Document Version:</b> 2.0", body_style), Paragraph("<b>Prepared For:</b> MLB Client Management", body_style)],
        [Paragraph("<b>Date:</b> September 05, 2026", body_style), Paragraph("<b>Currency:</b> Sri Lankan Rupee (LKR / Rs.)", body_style)]
    ]
    t_meta = Table(meta_data, colWidths=[270, 270])
    t_meta.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#fdf8f5')),
        ('PADDING', (0,0), (-1,-1), 6),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor('#ebdcd3')),
    ]))
    story.append(t_meta)
    story.append(Spacer(1, 10))

    # 1. Executive Summary
    story.append(Paragraph("1. Executive Summary", h1_style))
    story.append(Paragraph("<b>MLB POS System</b> is an end-to-end management solution designed for bakery operations, counter billing, raw ingredient inventory, and customer account management.", body_style))
    story.append(Paragraph("Following the successful deployment of Phase 1 (Web POS, Inventory BOM, Customer Profiles, and Executive Reporting), this proposal outlines <b>Phase 2 Technical Expansions</b> requested by MLB Management:", body_style))
    
    story.append(Paragraph("&bull; <b>SMS Gateway Integration:</b> Automated SMS notifications for receipts, custom cake status updates, and credit period reminders.", bullet_style))
    story.append(Paragraph("&bull; <b>Product Return & Refund Handling:</b> Complete workflow for customer exchanges, damaged stock classification, and credit notes.", bullet_style))
    story.append(Paragraph("&bull; <b>MLB Mobile Application (iOS & Android):</b> Mobile app suite featuring Owner Dashboard, Driver Delivery GPS Routing, and Mobile POS.", bullet_style))

    story.append(Spacer(1, 6))

    # 2. Detailed Modules Breakdown
    story.append(Paragraph("2. Proposed Phase 2 Technical Modules", h1_style))

    # Module 1
    story.append(Paragraph("Module 1: SMS Gateway Integration 📲", ParagraphStyle('H2', parent=body_style, fontSize=10.5, leading=13.5, textColor=colors.HexColor('#d97724'))))
    story.append(Paragraph("&bull; <b>Order Confirmation SMS:</b> Sends immediate SMS receipt to customers upon checkout containing Order # and Total Amount.", bullet_style))
    story.append(Paragraph("&bull; <b>Custom Order Status Alerts:</b> Automatic SMS when cake status changes (In Production &rarr; Ready &rarr; Out for Delivery).", bullet_style))
    story.append(Paragraph("&bull; <b>Credit Payment Reminders:</b> Automated SMS sent 3 days before credit period expiry for wholesale accounts.", bullet_style))

    story.append(Spacer(1, 4))

    # Module 2
    story.append(Paragraph("Module 2: Product Return & Refund Handling 🔄", ParagraphStyle('H2', parent=body_style, fontSize=10.5, leading=13.5, textColor=colors.HexColor('#d97724'))))
    story.append(Paragraph("&bull; <b>Customer Return Interface:</b> Search invoice #, select specific items returned, and select reason (Damaged/Expired, Exchange).", bullet_style))
    story.append(Paragraph("&bull; <b>Stock Classification:</b> Re-salable items are restocked automatically; damaged items are logged to Spoilage Inventory.", bullet_style))
    story.append(Paragraph("&bull; <b>Credit Notes & Refunds:</b> Generates printable Credit Notes or processes instant cash/UPI refunds.", bullet_style))

    story.append(Spacer(1, 4))

    # Module 3
    story.append(Paragraph("Module 3: MLB Mobile Application Suite 📱", ParagraphStyle('H2', parent=body_style, fontSize=10.5, leading=13.5, textColor=colors.HexColor('#d97724'))))
    story.append(Paragraph("&bull; <b>Owner Dashboard App:</b> Real-time sales ticker, revenue metrics, and push alerts for low stock.", bullet_style))
    story.append(Paragraph("&bull; <b>Delivery Driver App:</b> Scheduled delivery queue, Google Maps navigation, signature capture, and COD logging.", bullet_style))
    story.append(Paragraph("&bull; <b>Mobile Tablet POS:</b> Portable ordering interface for staff during peak hours.", bullet_style))

    story.append(Spacer(1, 8))

    # 3. Phased Implementation Roadmap
    story.append(Paragraph("3. Phased Implementation Roadmap", h1_style))
    
    roadmap_data = [
        [Paragraph("<b>Milestone</b>", body_style), Paragraph("<b>Description</b>", body_style), Paragraph("<b>Timeline</b>", body_style)],
        [Paragraph("Milestone 1", body_style), Paragraph("RESTful API Development & Security Authentication", body_style), Paragraph("1.5 Weeks", body_style)],
        [Paragraph("Milestone 2", body_style), Paragraph("SMS Gateway API Integration & Automated Triggers", body_style), Paragraph("1.0 Week", body_style)],
        [Paragraph("Milestone 3", body_style), Paragraph("Product Return, Credit Note & Waste Handling Module", body_style), Paragraph("1.5 Weeks", body_style)],
        [Paragraph("Milestone 4", body_style), Paragraph("Mobile App Development (Owner & Driver Apps)", body_style), Paragraph("3.0 Weeks", body_style)],
        [Paragraph("Milestone 5", body_style), Paragraph("UAT Testing, Final Deployment & Staff Training", body_style), Paragraph("1.0 Week", body_style)],
    ]
    t_road = Table(roadmap_data, colWidths=[90, 350, 100])
    t_road.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#ebdcd3')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#cccccc')),
        ('PADDING', (0,0), (-1,-1), 4),
    ]))
    story.append(t_road)

    story.append(Spacer(1, 8))

    # 4. Commercial Investment & Pricing Breakdown
    story.append(Paragraph("4. Commercial Investment & Pricing Breakdown 💰", h1_style))

    price_data = [
        [Paragraph("<b>Item / Scope</b>", body_style), Paragraph("<b>Description</b>", body_style), Paragraph("<b>Amount (LKR)</b>", body_style)],
        [Paragraph("System Development", body_style), Paragraph("Full Software Development (Web POS, SMS Module, Return Module, Mobile App Suite)", body_style), Paragraph("<b>Rs. 120,000.00</b>", body_style)],
        [Paragraph("Hosting / Servers / Users", body_style), Paragraph("Cloud Server Setup, SSL, Database Hosting, Domain & Multi-User Licenses", body_style), Paragraph("<b>Rs. 35,000.00</b>", body_style)],
        [Paragraph("<b>TOTAL INVESTMENT</b>", body_style), Paragraph("<b>Grand Total Commercial Investment</b>", body_style), Paragraph("<b>Rs. 155,000.00</b>", body_style)],
    ]
    t_price = Table(price_data, colWidths=[130, 270, 140])
    t_price.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#d97724')),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('BACKGROUND', (0,-1), (-1,-1), colors.HexColor('#fdf8f5')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#d97724')),
        ('PADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_price)

    story.append(Spacer(1, 10))

    # Footer note
    story.append(HRFlowable(width="100%", thickness=1, color=colors.HexColor('#dddddd'), spaceAfter=6))
    story.append(Paragraph("<font color='#777777'><i>This proposal document is prepared for client review and strategic planning. Codebase updates will commence upon client sign-off.</i></font>", ParagraphStyle('Foot', parent=body_style, fontSize=8, alignment=1)))

    doc.build(story)
    print(f"PDF created successfully at: {filename}")

if __name__ == '__main__':
    target_path1 = r"c:\xampp\htdocs\bakery manegement\project_proposal_mlb.pdf"
    target_path2 = r"C:\Users\USER\.gemini\antigravity\brain\85d72937-9964-4e5b-9bf3-d67a0114403b\project_proposal_mlb.pdf"
    build_pdf(target_path1)
    build_pdf(target_path2)
