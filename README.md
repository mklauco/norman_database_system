# NORMAN Database System
A comprehensive web-based platform for managing and accessing environmental monitoring data, focusing on emerging substances, antibiotic resistance, and various environmental matrices.

## Overview

The NORMAN Database System is a Laravel-based web application that serves as a central hub for environmental research data. It provides researchers, scientists, and environmental professionals with tools to search, analyze, and download data related to chemical occurrence, substance databases, ecotoxicology, and more.

## Key Features

### Multiple Database Modules
- **Chemical Occurrence Data (EMPODAT)** - Geo-referenced monitoring data on emerging substances
- **Substance Database (SUSDAT)** - Merged list of NORMAN substances for screening and prioritization
- **Indoor Environment** - Data from indoor environment matrices
- **Passive Sampling** - Data obtained with passive samplers
- **Bioassays Monitoring** - Environmental samples analyzed with bioassays
- **Antibiotic Resistant Bacteria/Genes (ARBG)** - ARBs/ARGs in environmental matrices
- **SARS-CoV-2 in Sewage** - European sewage monitoring data
- **Ecotoxicology** - Platform for systematic ecotoxicity studies evaluation
- **Prioritisation** - Results using NORMAN Prioritisation Framework

### Advanced Search & Filtering
- Multi-parameter search across different environmental matrices
- Country-based filtering
- Substance-specific queries
- Date range selections
- Concentration value filtering

### Data Management
- Bulk data import via CSV seeders
- Query logging and performance tracking
- Download capabilities for registered users
- Pagination for large datasets
- Real-time record counting

### User Management
- Authentication system with role-based access
- Guest browsing with limited features
- Admin panel for data management
- API resource management

## Main Notes

All documentation for this project is moved to internal doc repo: [https://github.com/mklauco/norman_database_system_internal_documentation](https://github.com/mklauco/norman_database_system_internal_documentation)

Server infrastructure is moved to: [https://github.com/mklauco/norman_database_system-infra](https://github.com/mklauco/norman_database_system-infra) a private repository to avoid potential security risks. Details will be shared upon request.

## Technology Stack

- **Backend**: Laravel 10+ (PHP)
- **Frontend**: Blade templates with Tailwind CSS
- **Database**: PostgreSQL
- **Authentication**: Laravel Breeze
- **Server Environment**: Docker
- **Data Processing**: CSV import/export capabilities
- **Maps**: Leaflet.js integration for geographical data

## Usage

### For General Users
1. Visit the homepage to browse available databases
2. Use the search and filter functions to find relevant data
3. View results in paginated tables
4. Register for an account to access download features

### For Researchers
1. Create an account to access full features
2. Use advanced filtering to narrow down datasets
3. Download filtered results as CSV files
4. Access API endpoints for programmatic data retrieval

### For Administrators
1. Access the admin dashboard after authentication
2. Manage database entities and user accounts
3. Monitor query logs and system performance
4. Upload new datasets via seeders

## Database Structure

The system manages multiple environmental databases:

- **Countries & Locations**: Geographical reference data
- **Substances**: Chemical compounds and their properties
- **Matrices**: Environmental sample types (water, soil, air, etc.)
- **Sampling Data**: Collection methods and dates
- **Concentration Data**: Measured values and units
- **Quality Assurance**: Method validation and data sources

## Key Components

### Models
- Eloquent models for each database module
- Relationship definitions between entities
- Data casting and formatting methods

### Controllers
- RESTful controllers for each module
- Search and filtering logic
- CSV export functionality
- Query logging and performance tracking

### Views
- Responsive Blade templates
- Tailwind CSS styling
- Interactive search forms
- Data visualization components

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write meaningful commit messages
- Include tests for new features
- Update documentation as needed

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: Check the `/docs` folder for detailed guides
- **Issues**: Report bugs via GitHub Issues
- **Email**: TBD

## Acknowledgments

- 

---

*The NORMAN Database System is designed to support environmental research and monitoring efforts worldwide. Together, we're building a comprehensive resource for understanding chemical occurrence and environmental health.*