-- Priests Table
CREATE TABLE Priests (
    PriestID INT AUTO_INCREMENT PRIMARY KEY, -- Unique ID for each priest
    PriestName VARCHAR(255) NOT NULL,        -- Name of the priest
    Availability ENUM('Available', 'Unavailable') DEFAULT 'Available' -- Priest availability
);

INSERT INTO `priests` (`PriestID`, `PriestName`, `Availability`) VALUES
(1, 'Fr. Fernando Peralta, SDB', 'Available'),
(2, 'Fr. Marcelino Benabaye, SDB', 'Available'),
(3, 'Fr. Leo Polutan, SDB', 'Available'),
(4, 'Fr. Genson Banguis, SDB', 'Available');


-- Upcoming Events Table (Must be created before SacramentRequests)
CREATE TABLE UpcomingEvents (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    EventDate DATE NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,
    SacramentType ENUM('Baptism', 'Confirmation', 'Wedding', 'First Communion', 'Funeral and Burial', 'Anointing of the Sick', 'Blessing') NOT NULL,
    PriestID INT,
    Status ENUM('Available', 'Booked', 'Pre-Booked') DEFAULT 'Available',
    FOREIGN KEY (PriestID) REFERENCES Priests(PriestID)
);

-- Users Table
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(255),
    LastName VARCHAR(255),
    Email VARCHAR(255) UNIQUE,
    Password VARCHAR(255),
    Role ENUM('admin', 'user') NOT NULL,
    Gender ENUM('Male', 'Female') NOT NULL,
    Address VARCHAR(255),

    Deleted TINYINT(1) DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `Password`, `Role`, `Gender`, `Address`, `CreatedAt`) VALUES
(1, 'Admin', 'admin', 'admin@example.com', '$2y$10$PP1yiT9mVVKVbYUJcZrs/OULUth1VkOe1hYP30PzO7tEM8tX5NGi2', 'admin', 'Male', 'parish', '2024-09-12 00:33:13');



CREATE TABLE SacramentRequests (
    RefNo VARCHAR(20) PRIMARY KEY,   
    UserID INT,                      
    SacramentType ENUM('Baptism', 'Confirmation', 'Wedding', 'Blessing', 'First Communion', 'Funeral and Burial', 'Anointing of the Sick') NOT NULL,
    PriestID INT,                    -- Foreign key to link with Priests table
    ScheduleDate DATE NOT NULL,
    ScheduleTime TIME NOT NULL,      
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    EventID INT NULL,                -- Event reference
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ProcessedBy INT NULL,
    ProcessedAt TIMESTAMP NULL,

    Deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (ProcessedBy) REFERENCES Users(UserID) ON DELETE SET NULL,
    FOREIGN KEY (EventID) REFERENCES UpcomingEvents(EventID) ON DELETE SET NULL,
    FOREIGN KEY (PriestID) REFERENCES Priests(PriestID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX (UserID)
);


CREATE TABLE Notifications (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    NotificationText VARCHAR(255),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsRead BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);


-- Baptism Request Table
CREATE TABLE BaptismRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),


    GKK_BEC VARCHAR(255),
    BirthCertNo VARCHAR(20),
    BaptismalDate DATE,
    Gender ENUM('Male', 'Female'),
    ChildName VARCHAR(255),
    ChildDOB DATE,
    ChildBPlace VARCHAR(255),
    FatherName VARCHAR(255),
    FatherBPlace VARCHAR(255),
    MotherMName VARCHAR(255),
    MotherBPlace VARCHAR(255),
    ParentsResidence VARCHAR(255),
    DMarriage DATE,
    MCertNo VARCHAR(20),
    PMarriage VARCHAR(255),
    MarriagePlace VARCHAR(255),
    PreferredBaptismDate DATE,
    PreferredBaptismTime TIME,


    ApprovedBy VARCHAR(255),
    ServicedBy VARCHAR(255),
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ProcessedAt DATE,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);

-- Godparents Table
CREATE TABLE Godparents (
    GodparentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  
    GodparentType ENUM('Godfather', 'Godmother'),  
    GodparentName VARCHAR(255),
    GodparentAddress VARCHAR(255),
    FOREIGN KEY (RequesterID) REFERENCES BaptismRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);

-- Baptism Uploaded Documents Table
CREATE TABLE BaptismUploadedDocuments (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  
    DocumentType ENUM('Birth Certificate', 'Marriage Certificate', 'GKK Certificate', 'GKK Certification Recommendation'),
    FilePath VARCHAR(500),  
    UploadedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequesterID) REFERENCES BaptismRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);


-- Wedding Request Table
CREATE TABLE WeddingRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),  

    ApplicantName VARCHAR(255),
    ApprovedBy VARCHAR(255),
    ServicedBy VARCHAR(255),
    PreferredWeddingDate DATE,
    PreferredWeddingTime TIME,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);

-- Wedding Uploaded Documents Table
CREATE TABLE WeddingUploadedDocuments (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  
    DocumentType ENUM('Popcom Certificate', 'Birth Certificate', 'Cenomar', 'Picture', 'Cedula', 'Baptismal Certificate', 'Confirmation Certificate', 'GKK Certification', 'GKK Parent Certification', 'GKK Sponsor Certification', 'Flam Interview', 'Pre-Cana Certificate', 'Request Bann Others Parish'),
    FilePath VARCHAR(500),
    UploadedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequesterID) REFERENCES WeddingRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);

-- Confirmation Request Table
CREATE TABLE ConfirmationRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),
    FullName VARCHAR(255), -- Pangalan (Full Name of the person making the request)
    FatherFullName VARCHAR(255), -- Father’s name
    MotherFullName VARCHAR(255), -- Mother’s name
    Residence VARCHAR(255), -- Residence
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ProcessedAt DATE,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ApprovedBy VARCHAR(255),
    ServicedBy VARCHAR(255),
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);

-- Confirmation Uploaded Documents Table
CREATE TABLE ConfirmationUploadedDocuments (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  -- Foreign key to the ConfirmationRequest table
    DocumentType ENUM('Roman Catholic Baptismal Certificate', 'Baptismal Certificate', 'Birth Certificate', 'Confirmation Recommendation') NOT NULL,
    FilePath VARCHAR(255),  -- Path to the uploaded file
    UploadedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequesterID) REFERENCES ConfirmationRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);

-- First Communion Request Table
CREATE TABLE FirstCommunionRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),

    FullNameChild VARCHAR(255), -- Full Name of the Child
    DateOfBirth DATE, -- Date of Birth of the Child
    PlaceOfBirth VARCHAR(255), -- Place of Birth of the Child
    BaptismalDate DATE, -- Baptismal Date of the Child
    BaptismalParish VARCHAR(255), -- Baptismal Parish of the Child
    FatherName VARCHAR(255), -- Father’s Name
    MotherName VARCHAR(255), -- Mother’s Name (Maiden Name)
    ParentGuardianName VARCHAR(255), -- Name of Parent/Guardian
    PhoneNumber VARCHAR(20), -- Phone number of Parent/Guardian
    EmailAddress VARCHAR(255), -- Email address of Parent/Guardian
    BaptismalCertificate VARCHAR(255), -- File path for Baptismal Certificate
    BEC_Certification VARCHAR(255), -- File path for BEC Certification
    ProofOfAddress VARCHAR(255), -- File path for Proof of Address
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending', -- Status of request
    ProcessedDate DATE, -- Date when processed
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date of request submission
    ApprovedBy VARCHAR(255), -- Person who approved the request
    ServicedBy VARCHAR(255), -- Person who serviced the request
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);

-- First Communion Uploaded Documents Table
CREATE TABLE FirstCommunionUploadedDocuments (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  -- Foreign key to the FirstCommunionRequest table
    DocumentType ENUM('Baptismal Certificate', 'BEC Certification', 'Proof of Address'),
    FilePath VARCHAR(255),  -- Path to uploaded file
    UploadedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequesterID) REFERENCES FirstCommunionRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);


-- Anointing of the Sick Request Table
CREATE TABLE AnointingOfTheSickRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),


    FullName VARCHAR(255),
    Address VARCHAR(255),
    Age INT,
    PhoneNumber VARCHAR(20),
    Gender ENUM('Male', 'Female'),
    LocationOfAnointing VARCHAR(255),
    PreferredDateTime DATETIME,

    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ProcessedDate DATE,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ApprovedBy VARCHAR(255),
    ServicedBy VARCHAR(255),
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);

CREATE TABLE AnointingOfTheSickRequestUploadedDocuments (
    DocumentID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,  
    DocumentType ENUM('Baptismal Certificate', 'Proof of Address'),
    FilePath VARCHAR(255),  -- Path to uploaded file
    UploadedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequesterID) REFERENCES AnointingOfTheSickRequest(RequesterID) ON DELETE CASCADE,
    INDEX (RequesterID)
);

-- Funeral and Burial Request Table
CREATE TABLE FuneralAndBurialRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),


    PreferredFuneralDate DATETIME, -- Updated to capture both date and time
    DeceasedFullName VARCHAR(255),
    DeceasedDateOfBirth DATE,
    DeceasedDateOfDeath DATE,
    Age INT, -- New column for age, optional
    CStatus VARCHAR(50), -- Civil status
    DFatherName VARCHAR(255),
    DMotherName VARCHAR(255),
    SpouseName VARCHAR(255), -- For married or cohabiting deceased
    CDeath VARCHAR(255), -- Cause of death
    Address VARCHAR(255), -- New column for the deceased's address
    BLocation VARCHAR(255), -- Burial location
    BMassTime TIME, -- Mass time for the burial (optional)
    FuneralServiceType VARCHAR(50), -- New column for funeral service type
    SacReceived VARCHAR(255), -- Sacraments received
    FamilyRepresentative VARCHAR(255), -- New column for family representative's name
    GKK VARCHAR(255), -- New column for GKK
    Parish VARCHAR(255), -- New column for parish
    President VARCHAR(255), -- New column for president
    VicePresident VARCHAR(255), -- New column for vice president
    Secretary VARCHAR(255), -- New column for secretary
    Treasurer VARCHAR(255), -- New column for treasurer
    PSPRepresentative VARCHAR(255), -- New column for PSP representative


    ProcessedDate DATE, -- Automatically captured when the request is processed
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ApprovedBy VARCHAR(255), -- Admin or user who approved the request
    ServicedBy VARCHAR(255), -- The person performing the funeral service
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending', -- Status of the request
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);


-- Updated Blessing Request Table
CREATE TABLE BlessingRequest (
    RequesterID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20),
    
    FullName VARCHAR(255),
    Address VARCHAR(255),
    RequesterContact VARCHAR(255),
    BlessingType ENUM('House Blessing', 'Vehicle Blessing', 'Office Blessing', 'Religious Articles', 'Other') NOT NULL,
    OtherBlessingType VARCHAR(255), -- Added this field to capture the "Other" blessing type when selected
    BlessingPlace VARCHAR(255), -- Location of the blessing (House, Office, Vehicle)

    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ProcessedDate DATE,
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ApprovedBy VARCHAR(255),
    ServicedBy VARCHAR(255),
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    INDEX (RefNo)
);


-- Feedback Table
CREATE TABLE Feedback (
    FeedbackID INT AUTO_INCREMENT PRIMARY KEY,
    RefNo VARCHAR(20), -- Reference number for the request being given feedback
    UserID INT, -- ID of the user providing the feedback (for traceability)

    FeedbackText TEXT, -- The actual feedback message
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Time of submission
    Status ENUM('Pending', 'Reviewed', 'Resolved') DEFAULT 'Pending', -- Status of feedback
    ProcessedDate DATE, -- Date when feedback was processed
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- Announcement Table
CREATE TABLE Announcement (
    AnnouncementID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    Title VARCHAR(255),
    Content TEXT,
    AnnouncementDates DATE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- Scheduling Table
CREATE TABLE SacramentScheduling (
    ScheduleID INT AUTO_INCREMENT PRIMARY KEY,  -- Unique ID for the schedule
    EventID INT NOT NULL,                       -- Foreign key referencing the event slot
    RefNo VARCHAR(20),                          -- Reference number from the SacramentRequests table
    UserID INT,                                 -- Foreign key to track which user booked the slot
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending', -- Status of the booking
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EventID) REFERENCES UpcomingEvents(EventID) ON DELETE CASCADE,
    FOREIGN KEY (RefNo) REFERENCES SacramentRequests(RefNo) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);

