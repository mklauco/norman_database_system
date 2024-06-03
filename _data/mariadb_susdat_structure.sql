-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 03, 2024 at 01:17 PM
-- Server version: 10.4.19-MariaDB-1:10.4.19+maria~xenial
-- PHP Version: 7.0.33-0ubuntu0.16.04.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `susdat`
--

-- --------------------------------------------------------

--
-- Table structure for table `susdat`
--

CREATE TABLE `susdat` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_name` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name IUPAC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Synonyms ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Reliability of Synonyms ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `sus_cas` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN PubChem` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN Cactus` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Reliability of CAS_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Validation Level` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `SMILES` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `SMILES Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `StdInChI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `StdInChIKey` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_SMILES` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_StdInChI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_StdInChIKey` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `PubChem_CID` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ChemSpiderID` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `DTXSID` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `Molecular_Formula` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Monoiso_Mass` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `[M+H]+` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `[M-H]-` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pred_RTI_Positive_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_RTI_pos` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pred_RTI_Negative_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_RTI_neg` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Tetrahymena_pyriformis_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `IGC50_48_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Tetrahymena_pyriformis_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Daphnia_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `LC50_48_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Daphnia_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Algae_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `EC50_72_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Algae_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pimephales_promelas_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `LC50_96_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Pimephales_promelas_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `logKow_EPISuite` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Exp_logKow_EPISuite` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ChemSpider ID based on InChIKey_19032018` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `alogp_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `xlogp_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Lowest P-PNEC (QSAR) [ug/L]` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Species` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ExposureScore_Water_KEMI` decimal(10,2) DEFAULT NULL,
  `HazScore_EcoChronic_KEMI` decimal(10,2) DEFAULT NULL,
  `ValidationLevel_KEMI` tinyint(4) DEFAULT NULL,
  `Prob_of_GC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. of GC',
  `Prob_RPLC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. RPLC',
  `Pred_Chromatography` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. Chromatography',
  `Prob_of_both_Ionization_Source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. of both Ionization Source',
  `Prob_EI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. EI',
  `Prob_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. ESI',
  `Pred_Ionization_source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. Ionization source',
  `Prob_both_ESI_mode` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. both ESI mode',
  `Prob_plusESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. +ESI',
  `Prob_minusESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. -ESI',
  `Pred_ESI_mode` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. ESI mode',
  `Preferable_Platform_by_decision_Tree` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Preferable Platform by decision Tree',
  `sus_synonyms` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `sus_remark` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sus_name_20231115` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sle_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` date DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_biblio`
--

CREATE TABLE `susdat_biblio` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `loq_biblio_min` float NOT NULL,
  `loq_biblio_max` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_cas_rn`
--

CREATE TABLE `susdat_cas_rn` (
  `sus_cas_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_cas_rn` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category`
--

CREATE TABLE `susdat_category` (
  `sus_cat_id` int(11) NOT NULL,
  `sus_cat_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_join`
--

CREATE TABLE `susdat_category_join` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_cat_id` int(11) NOT NULL,
  `sus_subcat_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_join_2`
--

CREATE TABLE `susdat_category_join_2` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_subcat_id` int(11) NOT NULL,
  `sus_subcat2_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_source`
--

CREATE TABLE `susdat_category_source` (
  `sus_cat_id` int(11) NOT NULL,
  `sus_cat_source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_sub_1`
--

CREATE TABLE `susdat_category_sub_1` (
  `sus_subcat_id` int(11) NOT NULL,
  `sus_cat_id` int(11) NOT NULL,
  `sus_subcat_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_sub_1_source`
--

CREATE TABLE `susdat_category_sub_1_source` (
  `sus_subcat_id` int(10) NOT NULL,
  `sus_subcat_source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_sub_2`
--

CREATE TABLE `susdat_category_sub_2` (
  `sus_subcat2_id` int(11) NOT NULL,
  `sus_subcat_id` int(11) NOT NULL,
  `sus_subcat2_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_category_sub_2_source`
--

CREATE TABLE `susdat_category_sub_2_source` (
  `sus_subcat2_id` int(10) NOT NULL,
  `sus_subcat2_source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_change`
--

CREATE TABLE `susdat_change` (
  `susch_id` int(10) UNSIGNED NOT NULL,
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `susch_item` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `susch_old` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `susch_new` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id` int(10) NOT NULL,
  `susch_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_change_id`
--

CREATE TABLE `susdat_change_id` (
  `suschid_id` int(10) UNSIGNED NOT NULL,
  `sus_id_old` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_id_new` int(8) UNSIGNED ZEROFILL NOT NULL,
  `suschid_db` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `suschid_table` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `suschid_count` int(11) NOT NULL,
  `id` int(10) NOT NULL,
  `suschid_date` datetime NOT NULL,
  `suschid_action` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_dashboard`
--

CREATE TABLE `susdat_dashboard` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `final_DTXSID` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `final_name_json` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_nis`
--

CREATE TABLE `susdat_nis` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_name` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sus_cas` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nis_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_pubchem_data`
--

CREATE TABLE `susdat_pubchem_data` (
  `PubChem_CID` int(11) NOT NULL,
  `PubChem_Type` tinyint(4) NOT NULL,
  `PubChem_InChIs` text NOT NULL,
  `PubChem_InChIKeys` varchar(30) NOT NULL,
  `PubChem_SMILES` text NOT NULL,
  `PubChem_IUPAC_Names` text NOT NULL,
  `PubChem_Titles` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_pubchem_join`
--

CREATE TABLE `susdat_pubchem_join` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `PubChem_CID` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_pubchem_json`
--

CREATE TABLE `susdat_pubchem_json` (
  `cid` int(11) NOT NULL,
  `json_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_pubchem_kovats_retention_index`
--

CREATE TABLE `susdat_pubchem_kovats_retention_index` (
  `PubChem_CID` int(11) NOT NULL,
  `PubChem_Type` varchar(50) NOT NULL,
  `PubChem_Title` varchar(255) NOT NULL,
  `PubChem_Standard_non_polar` text DEFAULT NULL,
  `PubChem_Semi_standard_non_polar` text DEFAULT NULL,
  `PubChem_Standard_polar` text DEFAULT NULL,
  `PubChem_All_column_types` text DEFAULT NULL,
  `PubChem_Created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_quarantine`
--

CREATE TABLE `susdat_quarantine` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_name` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Name IUPAC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Synonyms ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Reliability of Synonyms ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `sus_cas` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN PubChem` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN Cactus` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `CAS_RN ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Reliability of CAS_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Validation Level` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `SMILES` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `SMILES Dashboard` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `StdInChI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `StdInChIKey` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_SMILES` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_StdInChI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `MS_Ready_StdInChIKey` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `PubChem_CID` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ChemSpiderID` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `DTXSID` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `Molecular_Formula` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Monoiso_Mass` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `[M+H]+` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `[M-H]-` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pred_RTI_Positive_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_RTI_pos` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pred_RTI_Negative_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_RTI_neg` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Tetrahymena_pyriformis_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `IGC50_48_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Tetrahymena_pyriformis_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Daphnia_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `LC50_48_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Daphnia_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Algae_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `EC50_72_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Algae_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Pimephales_promelas_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `LC50_96_hr_ug/L` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty_Pimephales_promelas_toxicity` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `logKow_EPISuite` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Exp_logKow_EPISuite` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ChemSpider ID based on InChIKey_19032018` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `alogp_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `xlogp_ChemSpider` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Lowest P-PNEC (QSAR) [ug/L]` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Species` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `Uncertainty` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL,
  `ExposureScore_Water_KEMI` decimal(10,2) DEFAULT NULL,
  `HazScore_EcoChronic_KEMI` decimal(10,2) DEFAULT NULL,
  `ValidationLevel_KEMI` tinyint(4) DEFAULT NULL,
  `Prob_of_GC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. of GC',
  `Prob_RPLC` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. RPLC',
  `Pred_Chromatography` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. Chromatography',
  `Prob_of_both_Ionization_Source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. of both Ionization Source',
  `Prob_EI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. EI',
  `Prob_ESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. ESI',
  `Pred_Ionization_source` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. Ionization source',
  `Prob_both_ESI_mode` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. both ESI mode',
  `Prob_plusESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. +ESI',
  `Prob_minusESI` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Prob. -ESI',
  `Pred_ESI_mode` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Pred. ESI mode',
  `Preferable_Platform_by_decision_Tree` mediumtext CHARACTER SET utf8mb4 DEFAULT NULL COMMENT 'Preferable Platform by decision Tree',
  `sus_synonyms` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `sus_remark` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sus_name_20231115` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sle_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `quarantine_at` date DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_source`
--

CREATE TABLE `susdat_source` (
  `ss_id` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ss_abbreviation` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ss_description` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ss_order` smallint(6) NOT NULL,
  `ss_show` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_source_join`
--

CREATE TABLE `susdat_source_join` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `ss_id` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa`
--

CREATE TABLE `susdat_usepa` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `DTXSID` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `usepa_formula` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usepa_wikipedia` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Details >>  Wikipedia',
  `usepa_wikipedia_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Details >>  Wikipedia',
  `usepa_Log_Kow_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Experimental >> Average',
  `usepa_Log_Kow_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Predicted >> Average',
  `usepa_solubility_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Experimental >> Average',
  `usepa_solubility_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Predicted >> Average',
  `usepa_Koc_min_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Min value',
  `usepa_Koc_max_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Max value',
  `usepa_Koc_min_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >>Predicted >> Range >> Min value',
  `usepa_Koc_max_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Predicted >> Range >> Max value',
  `usepa_Life_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Experimental Average',
  `usepa_Life_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Predicted Average',
  `usepa_BCF_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Experimental Average',
  `usepa_BCF_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Predicted Average'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_20240428`
--

CREATE TABLE `susdat_usepa_20240428` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `DTXSID` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `usepa_formula` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usepa_wikipedia` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Details >>  Wikipedia',
  `usepa_wikipedia_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Details >>  Wikipedia',
  `usepa_Log_Kow_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Experimental >> Average',
  `usepa_Log_Kow_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  LogP: Octanol-Water  >> Predicted >> Average',
  `usepa_solubility_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Experimental >> Average',
  `usepa_solubility_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Properties >>  Water solubility >> Predicted >> Average',
  `usepa_Koc_min_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Min value',
  `usepa_Koc_max_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Max value',
  `usepa_Koc_min_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >>Predicted >> Range >> Min value',
  `usepa_Koc_max_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Predicted >> Range >> Max value',
  `usepa_Life_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Experimental Average',
  `usepa_Life_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Predicted Average',
  `usepa_BCF_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Experimental Average',
  `usepa_BCF_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Predicted Average'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_categories`
--

CREATE TABLE `susdat_usepa_categories` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `categories` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'EXPOSURE / Product & Use Category'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_data`
--

CREATE TABLE `susdat_usepa_data` (
  `DTXSID` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `found_by` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `dtx_sid` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `preferred_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `dtx_cid` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `casrn` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `inchikey` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `iupac_name` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `smiles` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `inchi_string` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ms_ready_smiles` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `qsar_ready_smiles` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `molecular_formula` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `average_mass` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `monoisotopic_mass` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `qc_level` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `safety_data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `expocast` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_sources` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `toxval_data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `number_of_pubmed_articles` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `pubchem_data_sources` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `cpdat_count` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `iris_link` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `pprtv_link` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `wikipedia_article` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `qc_notes` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `bioconcentration_factor_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `boiling_point_degc_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `48hr_daphnia_lc50_mol_l_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `density_g_cm_3_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `devtox_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `96hr_fathead_minnow_mol_l_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `flash_point_degc_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `melting_point_degc_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `ames_mutagenicity_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `oral_rat_ld50_mol_kg_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `surface_tension_dyn_cm_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `thermal_conductivity_mw_(m_k)_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `tetrahymena_pyriformis_igc50_mol_l_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `viscosity_cp_cp_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `vapor_pressure_mmhg_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `water_solubility_mol_l_test_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `atmospheric_hydroxylation_rate_(aoh)_cm3_molecule_sec_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `bioconcentration_factor_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `biodegradation_half_life_days_days_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `boiling_point_degc_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `henrys_law_atm-m3_mole_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `opera_km_days_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `octanol_air_partition_coeff_logkoa_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `soil_adsorption_coefficient_koc_l_kg_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `octanol_water_partition_logp_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `melting_point_degc_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `opera_pkaa_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `opera_pkab_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `vapor_pressure_mmhg_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `water_solubility_mol_l_opera_pred` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `expocast_median_exposure_prediction_mg_kg-bw_day` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `nhanes` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `toxcast_number_of_assays_total` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `toxcast_percent_active` text COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_fate`
--

CREATE TABLE `susdat_usepa_fate` (
  `id` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valueType` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dtxsid` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dtxcid` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpointName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultValue` double NOT NULL,
  `maxValue` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minValue` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelSource` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_fate_20240428`
--

CREATE TABLE `susdat_usepa_fate_20240428` (
  `id` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valueType` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dtxsid` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dtxcid` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpointName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultValue` double NOT NULL,
  `maxValue` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minValue` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelSource` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_fate_processing`
--

CREATE TABLE `susdat_usepa_fate_processing` (
  `dtxsid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `usepa_Koc_min_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Min value',
  `usepa_Koc_max_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Experimental >> Range >> Max value',
  `usepa_Koc_min_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >>Predicted >> Range >> Min value',
  `usepa_Koc_max_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Soil Adsorp. Coeff. >> Predicted >> Range >> Max value',
  `usepa_Life_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Experimental Average',
  `usepa_Life_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Biodeg. Half-Life >> Predicted Average',
  `usepa_BCF_experimental` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Experimental Average',
  `usepa_BCF_predicted` float DEFAULT NULL COMMENT 'Chemistry US EPA DashBoard >> Env. Fate/Transport >>  Bioconcentration Factor >> Predicted Average'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_functional`
--

CREATE TABLE `susdat_usepa_functional` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `functional` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'EXPOSURE / Product & Use Category'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `susdat_usepa_join`
--

CREATE TABLE `susdat_usepa_join` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `DTXSID` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT current_timestamp
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `susdat_biblio`
--
ALTER TABLE `susdat_biblio`
  ADD PRIMARY KEY (`sus_id`);

--
-- Indexes for table `susdat_cas_rn`
--
ALTER TABLE `susdat_cas_rn`
  ADD PRIMARY KEY (`sus_cas_id`,`sus_cas_rn`);

--
-- Indexes for table `susdat_category`
--
ALTER TABLE `susdat_category`
  ADD PRIMARY KEY (`sus_cat_id`);

--
-- Indexes for table `susdat_category_join`
--
ALTER TABLE `susdat_category_join`
  ADD UNIQUE KEY `sus_id_2` (`sus_id`,`sus_cat_id`,`sus_subcat_id`),
  ADD KEY `sus_subcat_id` (`sus_subcat_id`),
  ADD KEY `sus_cat_id` (`sus_cat_id`),
  ADD KEY `sus_id` (`sus_id`);

--
-- Indexes for table `susdat_category_join_2`
--
ALTER TABLE `susdat_category_join_2`
  ADD UNIQUE KEY `sus_id_2` (`sus_id`,`sus_subcat_id`,`sus_subcat2_id`),
  ADD KEY `sus_subcat_id` (`sus_subcat2_id`),
  ADD KEY `sus_cat_id` (`sus_subcat_id`),
  ADD KEY `sus_id` (`sus_id`);

--
-- Indexes for table `susdat_category_source`
--
ALTER TABLE `susdat_category_source`
  ADD PRIMARY KEY (`sus_cat_id`,`sus_cat_source`),
  ADD KEY `sus_cat_id` (`sus_cat_id`);

--
-- Indexes for table `susdat_category_sub_1`
--
ALTER TABLE `susdat_category_sub_1`
  ADD PRIMARY KEY (`sus_subcat_id`),
  ADD KEY `sus_cat_id` (`sus_cat_id`);

--
-- Indexes for table `susdat_category_sub_1_source`
--
ALTER TABLE `susdat_category_sub_1_source`
  ADD PRIMARY KEY (`sus_subcat_id`,`sus_subcat_source`),
  ADD KEY `sus_subcat_id` (`sus_subcat_id`);

--
-- Indexes for table `susdat_category_sub_2`
--
ALTER TABLE `susdat_category_sub_2`
  ADD PRIMARY KEY (`sus_subcat2_id`),
  ADD KEY `sus_cat_id` (`sus_subcat_id`);

--
-- Indexes for table `susdat_category_sub_2_source`
--
ALTER TABLE `susdat_category_sub_2_source`
  ADD PRIMARY KEY (`sus_subcat2_id`,`sus_subcat2_source`),
  ADD KEY `sus_subcat_id` (`sus_subcat2_id`);

--
-- Indexes for table `susdat_change`
--
ALTER TABLE `susdat_change`
  ADD PRIMARY KEY (`susch_id`);

--
-- Indexes for table `susdat_change_id`
--
ALTER TABLE `susdat_change_id`
  ADD PRIMARY KEY (`suschid_id`);

--
-- Indexes for table `susdat_dashboard`
--
ALTER TABLE `susdat_dashboard`
  ADD PRIMARY KEY (`sus_id`);

--
-- Indexes for table `susdat_nis`
--
ALTER TABLE `susdat_nis`
  ADD PRIMARY KEY (`sus_id`),
  ADD UNIQUE KEY `nis_id` (`nis_id`);

--
-- Indexes for table `susdat_pubchem_data`
--
ALTER TABLE `susdat_pubchem_data`
  ADD PRIMARY KEY (`PubChem_CID`);

--
-- Indexes for table `susdat_pubchem_kovats_retention_index`
--
ALTER TABLE `susdat_pubchem_kovats_retention_index`
  ADD PRIMARY KEY (`PubChem_CID`);

--
-- Indexes for table `susdat_source`
--
ALTER TABLE `susdat_source`
  ADD PRIMARY KEY (`ss_id`);

--
-- Indexes for table `susdat_source_join`
--
ALTER TABLE `susdat_source_join`
  ADD PRIMARY KEY (`sus_id`,`ss_id`),
  ADD KEY `sus_id` (`sus_id`),
  ADD KEY `ss_id` (`ss_id`);

--
-- Indexes for table `susdat_usepa`
--
ALTER TABLE `susdat_usepa`
  ADD PRIMARY KEY (`sus_id`),
  ADD KEY `DTXSID` (`DTXSID`);

--
-- Indexes for table `susdat_usepa_20240428`
--
ALTER TABLE `susdat_usepa_20240428`
  ADD PRIMARY KEY (`sus_id`),
  ADD KEY `DTXSID` (`DTXSID`);

--
-- Indexes for table `susdat_usepa_data`
--
ALTER TABLE `susdat_usepa_data`
  ADD PRIMARY KEY (`DTXSID`);

--
-- Indexes for table `susdat_usepa_fate`
--
ALTER TABLE `susdat_usepa_fate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `susdat_usepa_fate_20240428`
--
ALTER TABLE `susdat_usepa_fate_20240428`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `susdat_usepa_fate_processing`
--
ALTER TABLE `susdat_usepa_fate_processing`
  ADD PRIMARY KEY (`dtxsid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `susdat_category_sub_1`
--
ALTER TABLE `susdat_category_sub_1`
  MODIFY `sus_subcat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;
--
-- AUTO_INCREMENT for table `susdat_category_sub_2`
--
ALTER TABLE `susdat_category_sub_2`
  MODIFY `sus_subcat2_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `susdat_change`
--
ALTER TABLE `susdat_change`
  MODIFY `susch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111635;
--
-- AUTO_INCREMENT for table `susdat_change_id`
--
ALTER TABLE `susdat_change_id`
  MODIFY `suschid_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43664;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
