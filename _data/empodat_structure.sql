-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Hostiteľ: localhost
-- Čas generovania: St 29.Máj 2024, 11:34
-- Verzia serveru: 10.4.19-MariaDB-1:10.4.19+maria~xenial
-- Verzia PHP: 7.0.33-0ubuntu0.16.04.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáza: `empodat`
--

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `chemical_statistics`
--

CREATE TABLE `chemical_statistics` (
  `stat_analysis` int(10) UNSIGNED NOT NULL,
  `stat_analysis_total` int(10) UNSIGNED NOT NULL,
  `stat_compound` int(10) UNSIGNED NOT NULL,
  `stat_compound_total` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_addition`
--

CREATE TABLE `data_addition` (
  `dadd_id` tinyint(1) UNSIGNED NOT NULL,
  `dadd_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_air_filtration`
--

CREATE TABLE `data_air_filtration` (
  `dairf_id` tinyint(1) UNSIGNED NOT NULL,
  `dairf_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_analytical_method`
--

CREATE TABLE `data_analytical_method` (
  `dam_id` tinyint(2) NOT NULL,
  `dam_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_bioassay`
--

CREATE TABLE `data_bioassay` (
  `dbio_id` tinyint(3) UNSIGNED NOT NULL,
  `dbio_name` varchar(255) NOT NULL,
  `dbio_type` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_carrier`
--

CREATE TABLE `data_carrier` (
  `dcar_id` tinyint(1) UNSIGNED NOT NULL,
  `dcar_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category`
--

CREATE TABLE `data_category` (
  `dcat_id` tinyint(1) UNSIGNED NOT NULL,
  `dcat_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category_environment`
--

CREATE TABLE `data_category_environment` (
  `dcenv_id` tinyint(1) UNSIGNED NOT NULL,
  `dcenv_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category_enviro_h`
--

CREATE TABLE `data_category_enviro_h` (
  `dcaen_id` tinyint(1) UNSIGNED NOT NULL,
  `dcaen_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category_micro_p`
--

CREATE TABLE `data_category_micro_p` (
  `dcamp_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamp_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category_micro_s`
--

CREATE TABLE `data_category_micro_s` (
  `dcams_id` tinyint(1) UNSIGNED NOT NULL,
  `dcams_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_category_micro_t`
--

CREATE TABLE `data_category_micro_t` (
  `dcamt_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamt_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_compound`
--

CREATE TABLE `data_compound` (
  `nis_id` int(5) UNSIGNED NOT NULL,
  `nis_title` varchar(255) NOT NULL DEFAULT '',
  `nis_cas` varchar(50) NOT NULL,
  `lowest_pnec_water` double NOT NULL,
  `lowest_pnec_sediment` double NOT NULL,
  `lowest_pnec_biota` double NOT NULL,
  `lowest_pnec_biota_fish` double NOT NULL,
  `lowest_pnec_biota_mollusc` double NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_concentration_data`
--

CREATE TABLE `data_concentration_data` (
  `dcod_id` tinyint(1) UNSIGNED NOT NULL,
  `dcod_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_conditions`
--

CREATE TABLE `data_conditions` (
  `dcon_id` tinyint(1) UNSIGNED NOT NULL,
  `dcon_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_country`
--

CREATE TABLE `data_country` (
  `country` char(2) NOT NULL,
  `country_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_coverage_factor`
--

CREATE TABLE `data_coverage_factor` (
  `dcf_id` tinyint(1) UNSIGNED NOT NULL,
  `dcf_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_cto`
--

CREATE TABLE `data_cto` (
  `dcto_id` tinyint(1) UNSIGNED NOT NULL,
  `dcto_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_depth`
--

CREATE TABLE `data_depth` (
  `de_id` tinyint(1) UNSIGNED NOT NULL,
  `de_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_detector`
--

CREATE TABLE `data_detector` (
  `ddet_id` tinyint(1) UNSIGNED NOT NULL,
  `ddet_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_dilution`
--

CREATE TABLE `data_dilution` (
  `ddil_id` tinyint(1) UNSIGNED NOT NULL,
  `ddil_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_effect`
--

CREATE TABLE `data_effect` (
  `deff_id` tinyint(1) UNSIGNED NOT NULL,
  `deff_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_effluent_influent`
--

CREATE TABLE `data_effluent_influent` (
  `effluent_influent_id` tinyint(1) NOT NULL,
  `effluent_influent_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_endpoint`
--

CREATE TABLE `data_endpoint` (
  `dend_id` tinyint(1) UNSIGNED NOT NULL,
  `dend_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_exposition`
--

CREATE TABLE `data_exposition` (
  `dext_id` tinyint(1) UNSIGNED NOT NULL,
  `dext_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_field_work`
--

CREATE TABLE `data_field_work` (
  `field_id` tinyint(3) UNSIGNED NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_order` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_focus`
--

CREATE TABLE `data_focus` (
  `focus_id` tinyint(2) UNSIGNED NOT NULL,
  `focus_name` varchar(255) NOT NULL,
  `dpp_id` tinyint(2) UNSIGNED NOT NULL,
  `focus_order` tinyint(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_fraction`
--

CREATE TABLE `data_fraction` (
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `df_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_grain`
--

CREATE TABLE `data_grain` (
  `dgra_id` tinyint(1) UNSIGNED NOT NULL,
  `dgra_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_guideline`
--

CREATE TABLE `data_guideline` (
  `dgui_id` tinyint(1) UNSIGNED NOT NULL,
  `dgui_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_identification`
--

CREATE TABLE `data_identification` (
  `didea_id` tinyint(1) UNSIGNED NOT NULL,
  `didea_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_identified`
--

CREATE TABLE `data_identified` (
  `dide_id` tinyint(1) UNSIGNED NOT NULL,
  `dide_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_ind_concentration`
--

CREATE TABLE `data_ind_concentration` (
  `dic_id` tinyint(1) UNSIGNED NOT NULL,
  `dic_name` varchar(40) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_injector`
--

CREATE TABLE `data_injector` (
  `dinj_id` tinyint(1) UNSIGNED NOT NULL,
  `dinj_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_level_cooperation`
--

CREATE TABLE `data_level_cooperation` (
  `level_id` tinyint(1) UNSIGNED NOT NULL,
  `level_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_loc`
--

CREATE TABLE `data_loc` (
  `dloc_id` tinyint(1) UNSIGNED NOT NULL,
  `dloc_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_location`
--

CREATE TABLE `data_location` (
  `dloca_id` tinyint(1) UNSIGNED NOT NULL,
  `dloca_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_matrice`
--

CREATE TABLE `data_matrice` (
  `matrice_id` tinyint(2) UNSIGNED NOT NULL,
  `matrice_id1` tinyint(2) NOT NULL,
  `matrice_id2` tinyint(2) NOT NULL,
  `matrice_id3` tinyint(2) NOT NULL,
  `matrice_title1` varchar(30) NOT NULL,
  `matrice_title2` varchar(30) NOT NULL,
  `matrice_title3` varchar(30) NOT NULL,
  `matrice_title` varchar(255) NOT NULL,
  `matrice_sheet` tinyint(3) UNSIGNED NOT NULL,
  `matrice_unit` varchar(20) NOT NULL,
  `matrice_dct` tinyint(4) NOT NULL,
  `matrice_dct_name` tinytext NOT NULL,
  `matrice_prior_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_prior_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_a_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_a_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_b_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_b_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_c_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_c_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_d_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_d_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_matrice_20240223`
--

CREATE TABLE `data_matrice_20240223` (
  `matrice_id` tinyint(2) UNSIGNED NOT NULL,
  `matrice_id1` tinyint(2) NOT NULL,
  `matrice_id2` tinyint(2) NOT NULL,
  `matrice_id3` tinyint(2) NOT NULL,
  `matrice_title1` varchar(30) NOT NULL,
  `matrice_title2` varchar(30) NOT NULL,
  `matrice_title3` varchar(30) NOT NULL,
  `matrice_title` varchar(255) NOT NULL,
  `matrice_sheet` tinyint(3) UNSIGNED NOT NULL,
  `matrice_unit` varchar(20) NOT NULL,
  `matrice_dct` tinyint(4) NOT NULL,
  `matrice_dct_name` tinytext NOT NULL,
  `matrice_prior_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_prior_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_a_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_a_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_b_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_b_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_c_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_c_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_d_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_d_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_matrice_old`
--

CREATE TABLE `data_matrice_old` (
  `matrice_id` tinyint(2) UNSIGNED NOT NULL,
  `matrice_id1` tinyint(2) NOT NULL,
  `matrice_id2` tinyint(2) NOT NULL,
  `matrice_id3` tinyint(2) NOT NULL,
  `matrice_title1` varchar(30) NOT NULL,
  `matrice_title2` varchar(30) NOT NULL,
  `matrice_title3` varchar(30) NOT NULL,
  `matrice_title` varchar(255) NOT NULL,
  `matrice_sheet` tinyint(3) UNSIGNED NOT NULL,
  `matrice_unit` varchar(20) NOT NULL,
  `matrice_dct` tinyint(4) NOT NULL,
  `matrice_dct_name` tinytext NOT NULL,
  `matrice_prior_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_prior_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_a_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_a_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_b_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_b_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_c_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_c_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `matrice_scenar_d_id` tinyint(3) UNSIGNED NOT NULL,
  `matrice_scenar_d_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_mean_median`
--

CREATE TABLE `data_mean_median` (
  `dmm_id` tinyint(1) UNSIGNED NOT NULL,
  `dmm_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_measurement`
--

CREATE TABLE `data_measurement` (
  `dmeas_id` tinyint(1) UNSIGNED NOT NULL,
  `dmeas_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_organisation`
--

CREATE TABLE `data_organisation` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `organisation_name_en` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `organisation_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `acronym` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `department` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `street` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `pobox` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `zip` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `family_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mr_ms` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fax` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `web` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type_organisation` tinyint(2) UNSIGNED NOT NULL,
  `type_organisation_other` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `number_employees` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `activities_work_other` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `projects_id1` int(10) UNSIGNED NOT NULL,
  `projects_id2` int(10) UNSIGNED NOT NULL,
  `projects_id3` int(10) UNSIGNED NOT NULL,
  `projects_id4` int(10) UNSIGNED NOT NULL,
  `projects_id5` int(10) UNSIGNED NOT NULL,
  `projects_id6` int(10) UNSIGNED NOT NULL,
  `projects_id7` int(10) UNSIGNED NOT NULL,
  `projects_id8` int(10) UNSIGNED NOT NULL,
  `projects_id9` int(10) UNSIGNED NOT NULL,
  `projects_id10` int(10) UNSIGNED NOT NULL,
  `publication1` text COLLATE utf8_unicode_ci NOT NULL,
  `publication2` text COLLATE utf8_unicode_ci NOT NULL,
  `publication3` text COLLATE utf8_unicode_ci NOT NULL,
  `publication4` text COLLATE utf8_unicode_ci NOT NULL,
  `publication5` text COLLATE utf8_unicode_ci NOT NULL,
  `publication6` text COLLATE utf8_unicode_ci NOT NULL,
  `publication7` text COLLATE utf8_unicode_ci NOT NULL,
  `publication8` text COLLATE utf8_unicode_ci NOT NULL,
  `publication9` text COLLATE utf8_unicode_ci NOT NULL,
  `publication10` text COLLATE utf8_unicode_ci NOT NULL,
  `publication11` text COLLATE utf8_unicode_ci NOT NULL,
  `publication12` text COLLATE utf8_unicode_ci NOT NULL,
  `publication13` text COLLATE utf8_unicode_ci NOT NULL,
  `publication14` text COLLATE utf8_unicode_ci NOT NULL,
  `publication15` text COLLATE utf8_unicode_ci NOT NULL,
  `publication16` text COLLATE utf8_unicode_ci NOT NULL,
  `publication17` text COLLATE utf8_unicode_ci NOT NULL,
  `publication18` text COLLATE utf8_unicode_ci NOT NULL,
  `publication19` text COLLATE utf8_unicode_ci NOT NULL,
  `publication20` text COLLATE utf8_unicode_ci NOT NULL,
  `ecosystem` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `draft` tinyint(1) NOT NULL DEFAULT 0,
  `first_connect` datetime NOT NULL,
  `last_connect` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_particle_size`
--

CREATE TABLE `data_particle_size` (
  `dps_id` tinyint(1) UNSIGNED NOT NULL,
  `dps_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_precision`
--

CREATE TABLE `data_precision` (
  `dpre_id` tinyint(1) UNSIGNED NOT NULL,
  `dpre_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_precision_coordinates`
--

CREATE TABLE `data_precision_coordinates` (
  `dpc_id` tinyint(1) UNSIGNED NOT NULL,
  `dpc_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_preparation_method`
--

CREATE TABLE `data_preparation_method` (
  `dpm_id` tinyint(2) NOT NULL,
  `dpm_name` varchar(50) NOT NULL,
  `dpm_order` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures`
--

CREATE TABLE `data_pressures` (
  `dpr_id` tinyint(1) UNSIGNED NOT NULL,
  `dpr_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures1`
--

CREATE TABLE `data_pressures1` (
  `dprs_id` tinyint(1) UNSIGNED NOT NULL,
  `dprs_name` varchar(50) NOT NULL,
  `dprs_order` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures_h`
--

CREATE TABLE `data_pressures_h` (
  `dpreh_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreh_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures_o`
--

CREATE TABLE `data_pressures_o` (
  `dpreo_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreo_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures_s`
--

CREATE TABLE `data_pressures_s` (
  `dpres_id` tinyint(1) UNSIGNED NOT NULL,
  `dpres_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_pressures_t`
--

CREATE TABLE `data_pressures_t` (
  `dpret_id` tinyint(1) UNSIGNED NOT NULL,
  `dpret_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_project_primary`
--

CREATE TABLE `data_project_primary` (
  `dpp_id` tinyint(3) UNSIGNED NOT NULL,
  `dpp_name` varchar(255) NOT NULL,
  `dpp_table` varchar(50) NOT NULL,
  `dpp_order` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_protocols`
--

CREATE TABLE `data_protocols` (
  `dp_id` tinyint(1) UNSIGNED NOT NULL,
  `dp_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_proxy`
--

CREATE TABLE `data_proxy` (
  `dproxy_id` tinyint(1) UNSIGNED NOT NULL,
  `dproxy_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_reliability`
--

CREATE TABLE `data_reliability` (
  `drel_id` tinyint(1) UNSIGNED NOT NULL,
  `drel_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_sampling_method`
--

CREATE TABLE `data_sampling_method` (
  `dsa_id` tinyint(4) NOT NULL,
  `dsa_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dsa_type` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WASTE WATER: sampling method';

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_sampling_technique`
--

CREATE TABLE `data_sampling_technique` (
  `dst_id` tinyint(1) UNSIGNED NOT NULL,
  `dst_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_scd`
--

CREATE TABLE `data_scd` (
  `dscd_id` tinyint(1) UNSIGNED NOT NULL,
  `dscd_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_sewage_sludge`
--

CREATE TABLE `data_sewage_sludge` (
  `dss_id` tinyint(1) UNSIGNED NOT NULL,
  `dss_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_smo`
--

CREATE TABLE `data_smo` (
  `dsmo_id` tinyint(1) UNSIGNED NOT NULL,
  `dsmo_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_species_group`
--

CREATE TABLE `data_species_group` (
  `dsgr_id` tinyint(1) UNSIGNED NOT NULL,
  `dsgr_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_standardised_method`
--

CREATE TABLE `data_standardised_method` (
  `dsm_id` tinyint(1) NOT NULL,
  `dsm_name` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_summary_performance`
--

CREATE TABLE `data_summary_performance` (
  `dsp_id` tinyint(1) UNSIGNED NOT NULL,
  `dsp_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_tertiary_treatment`
--

CREATE TABLE `data_tertiary_treatment` (
  `dtt_id` tinyint(2) UNSIGNED NOT NULL,
  `dtt_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_tissue_element`
--

CREATE TABLE `data_tissue_element` (
  `dtiel_id` tinyint(1) UNSIGNED NOT NULL,
  `dtiel_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_toxicity`
--

CREATE TABLE `data_toxicity` (
  `dtox_id` tinyint(1) UNSIGNED NOT NULL,
  `dtox_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_treatment_less`
--

CREATE TABLE `data_treatment_less` (
  `dtl_id` tinyint(1) UNSIGNED NOT NULL,
  `dtl_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_treatment_plant`
--

CREATE TABLE `data_treatment_plant` (
  `dtp_id` tinyint(2) UNSIGNED NOT NULL,
  `dtp_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_data`
--

CREATE TABLE `data_type_data` (
  `dtod_id` tinyint(1) UNSIGNED NOT NULL,
  `dtod_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_monitoring`
--

CREATE TABLE `data_type_monitoring` (
  `dtm_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_name` varchar(40) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_organisation`
--

CREATE TABLE `data_type_organisation` (
  `dto_id` tinyint(3) UNSIGNED NOT NULL,
  `dto_name` varchar(255) NOT NULL,
  `dto_order` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_sampling`
--

CREATE TABLE `data_type_sampling` (
  `dtos_id` tinyint(1) UNSIGNED NOT NULL,
  `dtos_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_source`
--

CREATE TABLE `data_type_source` (
  `dts_id` tinyint(1) NOT NULL,
  `dts_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_type_waste`
--

CREATE TABLE `data_type_waste` (
  `dtw_id` tinyint(1) NOT NULL,
  `dtw_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_unit`
--

CREATE TABLE `data_unit` (
  `du_id` tinyint(1) UNSIGNED NOT NULL,
  `du_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `data_yes_no_questions`
--

CREATE TABLE `data_yes_no_questions` (
  `dynq_id` tinyint(1) UNSIGNED NOT NULL,
  `dynq_name` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis`
--

CREATE TABLE `dct_analysis` (
  `id` int(11) NOT NULL,
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `country` char(2) CHARACTER SET utf8 NOT NULL,
  `country_other` char(2) CHARACTER SET utf8 NOT NULL,
  `station_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `national_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `short_sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `provider_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `code_ec_wise` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_ec_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `east_west` tinyint(1) UNSIGNED NOT NULL,
  `longitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `north_south` tinyint(1) UNSIGNED NOT NULL,
  `latitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `dpc_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `altitude` varchar(20) CHARACTER SET utf8 NOT NULL,
  `specific_locations` tinytext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Specific locations',
  `matrix` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `matrix_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `compound` int(10) UNSIGNED DEFAULT NULL,
  `dcod_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `dic_id` tinyint(1) UNSIGNED NOT NULL,
  `concentration_value` double NOT NULL,
  `unit_extra` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tier` tinyint(1) NOT NULL,
  `sampling_technique` tinyint(1) NOT NULL,
  `sampling_date` datetime NOT NULL,
  `sampling_date_y` year(4) NOT NULL,
  `sampling_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_t` time NOT NULL,
  `sampling_duration_day` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sampling_duration_hour` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `id_method` int(10) NOT NULL DEFAULT 0,
  `id_data` int(10) NOT NULL DEFAULT 0,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `remark` text CHARACTER SET utf8 NOT NULL,
  `remark_add` text COLLATE utf8_unicode_ci NOT NULL,
  `show_date` int(11) NOT NULL,
  `dtod_id` int(10) UNSIGNED NOT NULL,
  `dtod_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_uncertainty` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dmm_id` int(10) UNSIGNED NOT NULL,
  `agg_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_number` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_deviation` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dtl_id` int(10) UNSIGNED NOT NULL,
  `dtl_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sampling_date1` datetime NOT NULL,
  `sampling_date1_y` year(4) NOT NULL,
  `sampling_date1_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_t` time NOT NULL,
  `analysis_date_y` year(4) NOT NULL,
  `analysis_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `analysis_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `dst_id` tinyint(1) NOT NULL,
  `dtos_id` tinyint(1) NOT NULL DEFAULT 0,
  `noexport` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `list_id` int(11) NOT NULL,
  `u_jds4` tinyint(1) NOT NULL DEFAULT 0,
  `u_ufz` tinyint(1) NOT NULL DEFAULT 0,
  `u_lptc` tinyint(1) NOT NULL DEFAULT 0,
  `u_nl` tinyint(1) NOT NULL DEFAULT 0,
  `u_connect` tinyint(1) NOT NULL DEFAULT 0,
  `u_preempt` tinyint(1) NOT NULL DEFAULT 0,
  `u_dnieper` tinyint(1) NOT NULL DEFAULT 0,
  `u_6rbmp` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_20220907`
--

CREATE TABLE `dct_analysis_20220907` (
  `id` int(11) NOT NULL,
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `country` char(2) CHARACTER SET utf8 NOT NULL,
  `country_other` char(2) CHARACTER SET utf8 NOT NULL,
  `station_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `national_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `short_sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `provider_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `code_ec_wise` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_ec_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `east_west` tinyint(1) UNSIGNED NOT NULL,
  `longitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `north_south` tinyint(1) UNSIGNED NOT NULL,
  `latitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `dpc_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `altitude` varchar(20) CHARACTER SET utf8 NOT NULL,
  `specific_locations` tinytext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Specific locations',
  `matrix` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `matrix_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `compound` int(10) UNSIGNED NOT NULL,
  `dcod_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `dic_id` tinyint(1) UNSIGNED NOT NULL,
  `concentration_value` double NOT NULL,
  `unit_extra` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tier` tinyint(1) NOT NULL,
  `sampling_technique` tinyint(1) NOT NULL,
  `sampling_date` datetime NOT NULL,
  `sampling_date_y` year(4) NOT NULL,
  `sampling_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_t` time NOT NULL,
  `sampling_duration_day` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sampling_duration_hour` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `id_method` int(10) NOT NULL DEFAULT 0,
  `id_data` int(10) NOT NULL DEFAULT 0,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `remark` text CHARACTER SET utf8 NOT NULL,
  `remark_add` text COLLATE utf8_unicode_ci NOT NULL,
  `show_date` int(11) NOT NULL,
  `dtod_id` int(10) UNSIGNED NOT NULL,
  `dtod_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_uncertainty` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dmm_id` int(10) UNSIGNED NOT NULL,
  `agg_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_number` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_deviation` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dtl_id` int(10) UNSIGNED NOT NULL,
  `dtl_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sampling_date1` datetime NOT NULL,
  `sampling_date1_y` year(4) NOT NULL,
  `sampling_date1_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_t` time NOT NULL,
  `analysis_date_y` year(4) NOT NULL,
  `analysis_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `analysis_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `dst_id` tinyint(1) NOT NULL,
  `noexport` tinyint(1) UNSIGNED NOT NULL,
  `list_id` int(11) NOT NULL,
  `u_jds4` tinyint(1) NOT NULL,
  `u_ufz` tinyint(1) NOT NULL DEFAULT 0,
  `u_lptc` tinyint(1) NOT NULL,
  `u_nl` tinyint(1) NOT NULL,
  `u_connect` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_20220918`
--

CREATE TABLE `dct_analysis_20220918` (
  `id` int(11) NOT NULL,
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `country` char(2) CHARACTER SET utf8 NOT NULL,
  `country_other` char(2) CHARACTER SET utf8 NOT NULL,
  `station_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `national_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `short_sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `provider_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `code_ec_wise` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_ec_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `east_west` tinyint(1) UNSIGNED NOT NULL,
  `longitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `north_south` tinyint(1) UNSIGNED NOT NULL,
  `latitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `dpc_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `altitude` varchar(20) CHARACTER SET utf8 NOT NULL,
  `specific_locations` tinytext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Specific locations',
  `matrix` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `matrix_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `compound` int(10) UNSIGNED NOT NULL,
  `dcod_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `dic_id` tinyint(1) UNSIGNED NOT NULL,
  `concentration_value` double NOT NULL,
  `unit_extra` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tier` tinyint(1) NOT NULL,
  `sampling_technique` tinyint(1) NOT NULL,
  `sampling_date` datetime NOT NULL,
  `sampling_date_y` year(4) NOT NULL,
  `sampling_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_t` time NOT NULL,
  `sampling_duration_day` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sampling_duration_hour` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `id_method` int(10) NOT NULL DEFAULT 0,
  `id_data` int(10) NOT NULL DEFAULT 0,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `remark` text CHARACTER SET utf8 NOT NULL,
  `remark_add` text COLLATE utf8_unicode_ci NOT NULL,
  `show_date` int(11) NOT NULL,
  `dtod_id` int(10) UNSIGNED NOT NULL,
  `dtod_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_uncertainty` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dmm_id` int(10) UNSIGNED NOT NULL,
  `agg_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_number` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_deviation` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dtl_id` int(10) UNSIGNED NOT NULL,
  `dtl_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sampling_date1` datetime NOT NULL,
  `sampling_date1_y` year(4) NOT NULL,
  `sampling_date1_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_t` time NOT NULL,
  `analysis_date_y` year(4) NOT NULL,
  `analysis_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `analysis_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `dst_id` tinyint(1) NOT NULL,
  `noexport` tinyint(1) UNSIGNED NOT NULL,
  `list_id` int(11) NOT NULL,
  `u_jds4` tinyint(1) NOT NULL,
  `u_ufz` tinyint(1) NOT NULL DEFAULT 0,
  `u_lptc` tinyint(1) NOT NULL,
  `u_nl` tinyint(1) NOT NULL,
  `u_connect` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_air_ambient`
--

CREATE TABLE `dct_analysis_air_ambient` (
  `id` int(11) NOT NULL,
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `height_level` varchar(255) NOT NULL,
  `barometric_pressure` varchar(255) NOT NULL,
  `humidity` varchar(255) NOT NULL,
  `wider_area` varchar(255) NOT NULL,
  `sea_level` varchar(255) NOT NULL,
  `wind_speed` varchar(255) NOT NULL,
  `wind_direction` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_air_emissions`
--

CREATE TABLE `dct_analysis_air_emissions` (
  `id` int(11) NOT NULL,
  `dtod_id` tinyint(1) NOT NULL,
  `dtod_other` varchar(255) NOT NULL,
  `agg_uncertainty` varchar(20) NOT NULL,
  `dmm_id` tinyint(1) NOT NULL,
  `agg_max` varchar(20) NOT NULL,
  `agg_min` varchar(20) NOT NULL,
  `agg_number` varchar(20) NOT NULL,
  `agg_deviation` varchar(20) NOT NULL,
  `dtl_id` tinyint(1) NOT NULL,
  `dtl_other` varchar(255) NOT NULL,
  `sampling_date1` datetime NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL DEFAULT '',
  `salinity_min` varchar(255) NOT NULL DEFAULT '',
  `salinity_mean` varchar(255) NOT NULL DEFAULT '',
  `salinity_max` varchar(255) NOT NULL DEFAULT '',
  `spm` varchar(255) NOT NULL DEFAULT '',
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `type_industry` varchar(255) NOT NULL DEFAULT '',
  `capacity` varchar(255) NOT NULL DEFAULT '',
  `flow` varchar(255) NOT NULL DEFAULT '',
  `dtp_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtp_other` varchar(255) NOT NULL,
  `dtt_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtt_other` varchar(255) NOT NULL,
  `dry_matter` varchar(255) NOT NULL DEFAULT '',
  `srt` varchar(255) NOT NULL DEFAULT '',
  `reactor` varchar(255) NOT NULL DEFAULT '',
  `height_level` varchar(255) NOT NULL,
  `barometric_pressure` varchar(255) NOT NULL,
  `humidity` varchar(255) NOT NULL,
  `flow_rate` varchar(255) NOT NULL,
  `load_gs` varchar(255) NOT NULL,
  `factor` varchar(255) NOT NULL,
  `dairf_id` tinyint(1) UNSIGNED NOT NULL,
  `wider_area` varchar(255) NOT NULL,
  `dcenv_id` tinyint(1) UNSIGNED NOT NULL,
  `sea_level` varchar(255) NOT NULL,
  `wind_speed` varchar(255) NOT NULL,
  `wind_direction` varchar(255) NOT NULL,
  `sector_activity` varchar(255) NOT NULL,
  `air_renewal` varchar(255) NOT NULL,
  `type_air_renewal` varchar(255) NOT NULL,
  `air_filtered` varchar(255) NOT NULL,
  `type_filter` varchar(255) NOT NULL,
  `dloca_id` tinyint(1) UNSIGNED NOT NULL,
  `dcaen_id` tinyint(1) UNSIGNED NOT NULL,
  `dcams_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamt_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamp_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreh_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreo_id` tinyint(1) UNSIGNED NOT NULL,
  `dpres_id` tinyint(1) UNSIGNED NOT NULL,
  `dpret_id` tinyint(1) UNSIGNED NOT NULL,
  `height_floor` varchar(255) NOT NULL,
  `number_floor` varchar(255) NOT NULL,
  `room_street` tinyint(1) UNSIGNED NOT NULL,
  `dsgr_id` tinyint(1) UNSIGNED NOT NULL,
  `dsgr_other` varchar(255) NOT NULL,
  `species_name` varchar(255) NOT NULL,
  `species_alive` varchar(255) NOT NULL,
  `dmeas_id` tinyint(1) UNSIGNED NOT NULL,
  `dtiel_id` tinyint(1) UNSIGNED NOT NULL,
  `biota_size` varchar(255) NOT NULL,
  `biota_weight` varchar(255) NOT NULL,
  `number_organisms` varchar(255) NOT NULL,
  `dry_wet` varchar(255) NOT NULL,
  `fat_content` varchar(255) NOT NULL,
  `soil_type` varchar(255) NOT NULL,
  `soil_texture` varchar(255) NOT NULL,
  `dps_id` tinyint(1) UNSIGNED NOT NULL,
  `dgra_id` tinyint(1) UNSIGNED NOT NULL,
  `description_sampling` varchar(255) NOT NULL,
  `df1_id` tinyint(1) UNSIGNED NOT NULL,
  `df1_other` varchar(255) NOT NULL,
  `dss_id` tinyint(1) UNSIGNED NOT NULL,
  `dss_other` varchar(255) NOT NULL,
  `distance` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_air_indoor`
--

CREATE TABLE `dct_analysis_air_indoor` (
  `id` int(11) NOT NULL,
  `dtod_id` tinyint(1) NOT NULL,
  `dtod_other` varchar(255) NOT NULL,
  `agg_uncertainty` varchar(20) NOT NULL,
  `dmm_id` tinyint(1) NOT NULL,
  `agg_max` varchar(20) NOT NULL,
  `agg_min` varchar(20) NOT NULL,
  `agg_number` varchar(20) NOT NULL,
  `agg_deviation` varchar(20) NOT NULL,
  `dtl_id` tinyint(1) NOT NULL,
  `dtl_other` varchar(255) NOT NULL,
  `sampling_date1` datetime DEFAULT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL DEFAULT '',
  `salinity_min` varchar(255) NOT NULL DEFAULT '',
  `salinity_mean` varchar(255) NOT NULL DEFAULT '',
  `salinity_max` varchar(255) NOT NULL DEFAULT '',
  `spm` varchar(255) NOT NULL DEFAULT '',
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `type_industry` varchar(255) NOT NULL DEFAULT '',
  `capacity` varchar(255) NOT NULL DEFAULT '',
  `flow` varchar(255) NOT NULL DEFAULT '',
  `dtp_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtp_other` varchar(255) NOT NULL,
  `dtt_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtt_other` varchar(255) NOT NULL,
  `dry_matter` varchar(255) NOT NULL DEFAULT '',
  `srt` varchar(255) NOT NULL DEFAULT '',
  `reactor` varchar(255) NOT NULL DEFAULT '',
  `height_level` varchar(255) NOT NULL,
  `barometric_pressure` varchar(255) NOT NULL,
  `humidity` varchar(255) NOT NULL,
  `flow_rate` varchar(255) NOT NULL,
  `load_gs` varchar(255) NOT NULL,
  `factor` varchar(255) NOT NULL,
  `dairf_id` tinyint(1) UNSIGNED NOT NULL,
  `wider_area` varchar(255) NOT NULL,
  `dcenv_id` tinyint(1) UNSIGNED NOT NULL,
  `sea_level` varchar(255) NOT NULL,
  `wind_speed` varchar(255) NOT NULL,
  `wind_direction` varchar(255) NOT NULL,
  `sector_activity` varchar(255) NOT NULL,
  `air_renewal` varchar(255) NOT NULL,
  `type_air_renewal` varchar(255) NOT NULL,
  `air_filtered` varchar(255) NOT NULL,
  `type_filter` varchar(255) NOT NULL,
  `dloca_id` tinyint(1) UNSIGNED NOT NULL,
  `dcaen_id` tinyint(1) UNSIGNED NOT NULL,
  `dcams_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamt_id` tinyint(1) UNSIGNED NOT NULL,
  `dcamp_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreh_id` tinyint(1) UNSIGNED NOT NULL,
  `dpreo_id` tinyint(1) UNSIGNED NOT NULL,
  `dpres_id` tinyint(1) UNSIGNED NOT NULL,
  `dpret_id` tinyint(1) UNSIGNED NOT NULL,
  `height_floor` varchar(255) NOT NULL,
  `number_floor` varchar(255) NOT NULL,
  `room_street` tinyint(1) UNSIGNED NOT NULL,
  `dsgr_id` tinyint(1) UNSIGNED NOT NULL,
  `dsgr_other` varchar(255) NOT NULL,
  `species_name` varchar(255) NOT NULL,
  `species_alive` varchar(255) NOT NULL,
  `dmeas_id` tinyint(1) UNSIGNED NOT NULL,
  `dtiel_id` tinyint(1) UNSIGNED NOT NULL,
  `biota_size` varchar(255) NOT NULL,
  `biota_weight` varchar(255) NOT NULL,
  `number_organisms` varchar(255) NOT NULL,
  `dry_wet` varchar(255) NOT NULL,
  `fat_content` varchar(255) NOT NULL,
  `soil_type` varchar(255) NOT NULL,
  `soil_texture` varchar(255) NOT NULL,
  `dps_id` tinyint(1) UNSIGNED NOT NULL,
  `dgra_id` tinyint(1) UNSIGNED NOT NULL,
  `description_sampling` varchar(255) NOT NULL,
  `df1_id` tinyint(1) UNSIGNED NOT NULL,
  `df1_other` varchar(255) NOT NULL,
  `dss_id` tinyint(1) UNSIGNED NOT NULL,
  `dss_other` varchar(255) NOT NULL,
  `distance` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_air_outdoor`
--

CREATE TABLE `dct_analysis_air_outdoor` (
  `id` int(11) NOT NULL,
  `flow_rate` varchar(255) NOT NULL,
  `dcto_id` tinyint(1) UNSIGNED NOT NULL,
  `dloc_id` tinyint(1) UNSIGNED NOT NULL,
  `wider_area` varchar(255) NOT NULL,
  `ground_level` varchar(255) NOT NULL,
  `sea_level` varchar(255) NOT NULL,
  `dproxy_id` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_air_workplace`
--

CREATE TABLE `dct_analysis_air_workplace` (
  `id` int(11) NOT NULL,
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `height_level` varchar(255) NOT NULL,
  `barometric_pressure` varchar(255) NOT NULL,
  `humidity` varchar(255) NOT NULL,
  `wider_area` varchar(255) NOT NULL,
  `dcenv_id` tinyint(1) UNSIGNED NOT NULL,
  `sea_level` varchar(255) NOT NULL,
  `wind_speed` varchar(255) NOT NULL,
  `wind_direction` varchar(255) NOT NULL,
  `sector_activity` varchar(255) NOT NULL,
  `air_renewal` varchar(255) NOT NULL,
  `type_air_renewal` varchar(255) NOT NULL,
  `air_filtered` varchar(255) NOT NULL,
  `type_filter` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_biota`
--

CREATE TABLE `dct_analysis_biota` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT 'Name of river / estuary / lake / reservoir / sea',
  `basin_name` varchar(255) DEFAULT NULL COMMENT 'River basin name',
  `km` varchar(255) DEFAULT NULL COMMENT 'River-km',
  `dpr_id` tinyint(2) DEFAULT 0 COMMENT 'Proxy pressures',
  `dsgr_id` tinyint(1) DEFAULT 0 COMMENT 'Species group',
  `dsgr_other` varchar(255) DEFAULT NULL COMMENT 'Species group - other',
  `species` tinytext DEFAULT NULL COMMENT 'Species',
  `species_name` varchar(255) DEFAULT NULL COMMENT 'Species name (in Latin)',
  `species_alive` varchar(255) DEFAULT NULL COMMENT '???????????',
  `dmeas_id` tinyint(1) UNSIGNED DEFAULT NULL COMMENT 'Basis of measurement',
  `dmeas_other` varchar(255) DEFAULT NULL COMMENT 'Basis of measurement - other',
  `dtiel_id` tinyint(1) UNSIGNED DEFAULT NULL COMMENT 'Tissue element of species monitored (lyophilized)',
  `dtiel_other` varchar(255) DEFAULT NULL COMMENT 'Tissue element of species monitored (lyophilized) - other',
  `biota_size` varchar(255) DEFAULT NULL COMMENT 'Biota size [mm]',
  `biota_length` tinytext DEFAULT NULL COMMENT 'Biota length  [mm]',
  `biota_weight` varchar(255) DEFAULT NULL COMMENT 'Biota weight [kg]',
  `biota_sex` tinytext DEFAULT NULL COMMENT 'Biota sex',
  `biota_age` tinytext DEFAULT NULL COMMENT 'Biota age',
  `agegroup` tinytext DEFAULT NULL COMMENT 'Agegroup',
  `number_organisms` varchar(255) DEFAULT NULL COMMENT 'Number of organisms used',
  `water_content` tinytext DEFAULT NULL COMMENT 'Water content of tissue [%]',
  `dry_wet` varchar(255) DEFAULT NULL COMMENT 'Dry Wet Ratio [weight %]',
  `fat_content` varchar(255) DEFAULT NULL COMMENT 'Fat content of tissue  [%]',
  `nutrition_condition` int(11) DEFAULT NULL COMMENT 'Nutrition condition',
  `no_pooled_individuals` tinytext DEFAULT NULL COMMENT 'No. of pooled individuals',
  `standardised_protocols` tinytext DEFAULT NULL COMMENT 'Standardised protocols for dissection of organs available',
  `time_freezing` tinytext DEFAULT NULL COMMENT 'Time of freezing',
  `storage_temperature` tinytext DEFAULT NULL COMMENT 'Storage temperature',
  `packing_material` tinytext DEFAULT NULL COMMENT 'Packing material of samples',
  `geographic_range` tinytext DEFAULT NULL COMMENT 'Geographic range of pooled individuals',
  `was_species_alive` tinytext DEFAULT NULL COMMENT 'Was species alive (terrestrial and marine mammals)?',
  `receive_medical_treatment` tinytext DEFAULT NULL COMMENT 'Did a receive medical treatment prior to death?',
  `was_species_euthanised` tinytext DEFAULT NULL COMMENT 'Was the species euthanised?',
  `year_death` varchar(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_old`
--

CREATE TABLE `dct_analysis_old` (
  `id` int(11) NOT NULL,
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `country` char(2) CHARACTER SET utf8 NOT NULL,
  `country_other` char(2) CHARACTER SET utf8 NOT NULL,
  `station_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `national_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `short_sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `sample_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `provider_code` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `code_ec_wise` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_ec_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `code_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `east_west` tinyint(3) UNSIGNED NOT NULL,
  `longitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `longitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `north_south` tinyint(3) UNSIGNED NOT NULL,
  `latitude_d` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_m` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_s` varchar(10) CHARACTER SET utf8 NOT NULL,
  `latitude_decimal` varchar(20) CHARACTER SET utf8 NOT NULL,
  `dpc_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `altitude` varchar(20) CHARACTER SET utf8 NOT NULL,
  `specific_locations` tinytext COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Specific locations',
  `matrix` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `matrix_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `compound` int(10) UNSIGNED NOT NULL,
  `dcod_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `dic_id` tinyint(3) UNSIGNED NOT NULL,
  `concentration_value` double NOT NULL,
  `unit_extra` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tier` tinyint(1) NOT NULL,
  `sampling_technique` tinyint(1) NOT NULL,
  `sampling_date` datetime NOT NULL,
  `sampling_date_y` year(4) NOT NULL,
  `sampling_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date_t` time NOT NULL,
  `sampling_duration_day` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sampling_duration_hour` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `id_method` int(11) NOT NULL DEFAULT 0,
  `id_data` int(11) NOT NULL DEFAULT 0,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `remark` text CHARACTER SET utf8 NOT NULL,
  `remark_add` text COLLATE utf8_unicode_ci NOT NULL,
  `show_date` int(11) NOT NULL,
  `dtod_id` int(10) UNSIGNED NOT NULL,
  `dtod_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_uncertainty` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dmm_id` int(10) UNSIGNED NOT NULL,
  `agg_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_number` varchar(255) CHARACTER SET utf8 NOT NULL,
  `agg_deviation` varchar(255) CHARACTER SET utf8 NOT NULL,
  `dtl_id` int(10) UNSIGNED NOT NULL,
  `dtl_other` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sampling_date1` datetime NOT NULL,
  `sampling_date1_y` year(4) NOT NULL,
  `sampling_date1_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `sampling_date1_t` time NOT NULL,
  `analysis_date_y` year(4) NOT NULL,
  `analysis_date_m` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `analysis_date_d` tinyint(2) UNSIGNED ZEROFILL NOT NULL,
  `dst_id` tinyint(1) NOT NULL,
  `dtos_id` tinyint(1) NOT NULL DEFAULT 0,
  `noexport` tinyint(3) UNSIGNED NOT NULL,
  `list_id` int(11) NOT NULL,
  `u_jds4` tinyint(1) NOT NULL,
  `u_ufz` tinyint(1) NOT NULL DEFAULT 0,
  `u_lptc` tinyint(1) NOT NULL,
  `u_nl` tinyint(1) NOT NULL,
  `u_connect` tinyint(1) NOT NULL,
  `u_preempt` tinyint(4) NOT NULL,
  `u_dnieper` tinyint(1) NOT NULL DEFAULT 0,
  `u_6rbmp` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_sediments`
--

CREATE TABLE `dct_analysis_sediments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `df_id` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `df_other` varchar(255) NOT NULL,
  `dcat_id` tinyint(2) NOT NULL DEFAULT 0,
  `dcat_other` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_sewage_sludge`
--

CREATE TABLE `dct_analysis_sewage_sludge` (
  `id` int(11) NOT NULL,
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `type_industry` varchar(255) NOT NULL DEFAULT '',
  `capacity` varchar(255) NOT NULL DEFAULT '',
  `dtp_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtp_other` varchar(255) NOT NULL,
  `dtt_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtt_other` varchar(255) NOT NULL,
  `srt` varchar(255) NOT NULL DEFAULT '',
  `reactor` varchar(255) NOT NULL DEFAULT '',
  `description_sampling` varchar(255) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `df_other` varchar(255) NOT NULL,
  `dss_id` tinyint(1) UNSIGNED NOT NULL,
  `dss_other` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_soil`
--

CREATE TABLE `dct_analysis_soil` (
  `id` int(11) NOT NULL,
  `basin_name` varchar(255) NOT NULL,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `ph` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `wider_area` varchar(255) NOT NULL,
  `dry_wet` varchar(255) NOT NULL,
  `soil_type` varchar(255) NOT NULL,
  `soil_texture` varchar(255) NOT NULL,
  `dps_id` tinyint(1) UNSIGNED NOT NULL,
  `dgra_id` tinyint(1) UNSIGNED NOT NULL,
  `dgra_other` varchar(255) NOT NULL,
  `km` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_spm`
--

CREATE TABLE `dct_analysis_spm` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `depth_m` varchar(255) NOT NULL,
  `spm` varchar(255) NOT NULL,
  `spm_orig` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL,
  `carbon_orig` varchar(255) NOT NULL DEFAULT '',
  `distance` varchar(255) NOT NULL,
  `end_east_west` tinyint(1) UNSIGNED NOT NULL,
  `end_longitude_d` varchar(10) NOT NULL,
  `end_longitude_m` varchar(10) NOT NULL,
  `end_longitude_s` varchar(10) NOT NULL,
  `end_longitude_decimal` varchar(20) NOT NULL,
  `end_north_south` tinyint(1) UNSIGNED NOT NULL,
  `end_latitude_d` varchar(10) NOT NULL,
  `end_latitude_m` varchar(10) NOT NULL,
  `end_latitude_s` varchar(10) NOT NULL,
  `end_latitude_decimal` varchar(20) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_ground`
--

CREATE TABLE `dct_analysis_water_ground` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL,
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `doc_mg_cl` tinytext NOT NULL,
  `o2_mg_l` tinytext NOT NULL,
  `dcat_id` tinyint(1) NOT NULL DEFAULT 0,
  `dcat_other` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_surface`
--

CREATE TABLE `dct_analysis_water_surface` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL DEFAULT '',
  `salinity_min` varchar(255) NOT NULL DEFAULT '',
  `salinity_mean` varchar(255) NOT NULL DEFAULT '',
  `salinity_max` varchar(255) NOT NULL DEFAULT '',
  `spm` varchar(255) NOT NULL DEFAULT '',
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `horizon` tinytext NOT NULL,
  `doc_mg_cl` tinytext NOT NULL,
  `o2_mg_l` tinytext NOT NULL,
  `dcat_id` tinyint(2) NOT NULL DEFAULT 0,
  `dcat_other` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_surface_20220918`
--

CREATE TABLE `dct_analysis_water_surface_20220918` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL DEFAULT '',
  `salinity_min` varchar(255) NOT NULL DEFAULT '',
  `salinity_mean` varchar(255) NOT NULL DEFAULT '',
  `salinity_max` varchar(255) NOT NULL DEFAULT '',
  `spm` varchar(255) NOT NULL DEFAULT '',
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `horizon` tinytext NOT NULL,
  `doc_mg_cl` tinytext NOT NULL,
  `o2_mg_l` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_surface_old`
--

CREATE TABLE `dct_analysis_water_surface_old` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `km` varchar(255) NOT NULL DEFAULT '',
  `dpr_id` tinyint(4) NOT NULL DEFAULT 0,
  `de_id` tinyint(1) NOT NULL DEFAULT 0,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL DEFAULT '',
  `salinity_min` varchar(255) NOT NULL DEFAULT '',
  `salinity_mean` varchar(255) NOT NULL DEFAULT '',
  `salinity_max` varchar(255) NOT NULL DEFAULT '',
  `spm` varchar(255) NOT NULL DEFAULT '',
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `horizon` tinytext NOT NULL,
  `doc_mg_cl` tinytext NOT NULL,
  `o2_mg_l` tinytext NOT NULL,
  `dcat_id` tinyint(4) NOT NULL DEFAULT 0,
  `dcat_other` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_waste`
--

CREATE TABLE `dct_analysis_water_waste` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(1) UNSIGNED NOT NULL,
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `dpr_id` tinyint(2) NOT NULL DEFAULT 0,
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `type_industry` varchar(255) NOT NULL DEFAULT '',
  `capacity` varchar(255) NOT NULL DEFAULT '',
  `flow` varchar(255) NOT NULL DEFAULT '',
  `effluent_influent_id` tinyint(4) DEFAULT NULL,
  `effluent_influent_other` varchar(50) DEFAULT NULL,
  `dtw_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Type of wastewater',
  `dtp_id` tinyint(1) NOT NULL DEFAULT 0,
  `dtp_other` varchar(255) DEFAULT NULL,
  `dtt_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Advanced treatment steps (data_tertiary_treatment)',
  `dry_matter` varchar(255) NOT NULL DEFAULT '',
  `srt` varchar(255) NOT NULL DEFAULT '',
  `reactor` varchar(255) NOT NULL DEFAULT '',
  `dsa_id` tinyint(4) NOT NULL COMMENT 'Sampling method',
  `de_id` tinyint(1) NOT NULL,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analysis_water_waste_old`
--

CREATE TABLE `dct_analysis_water_waste_old` (
  `id` int(11) NOT NULL,
  `df_id` tinyint(3) UNSIGNED NOT NULL,
  `basin_name` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `dpr_id` tinyint(4) NOT NULL DEFAULT 0,
  `ph` varchar(255) NOT NULL DEFAULT '',
  `temperature` varchar(255) NOT NULL DEFAULT '',
  `carbon` varchar(255) NOT NULL DEFAULT '',
  `hardness` varchar(255) NOT NULL DEFAULT '',
  `type_industry` varchar(255) NOT NULL DEFAULT '',
  `capacity` varchar(255) NOT NULL DEFAULT '',
  `flow` varchar(255) NOT NULL DEFAULT '',
  `dtw_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Type of wastewater',
  `dtp_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Type of treatment plant associated with the parameter',
  `dtp_other` varchar(255) DEFAULT NULL,
  `dtt_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Advanced treatment steps (data_tertiary_treatment)',
  `dry_matter` varchar(255) NOT NULL DEFAULT '',
  `srt` varchar(255) NOT NULL DEFAULT '',
  `reactor` varchar(255) NOT NULL DEFAULT '',
  `dsa_id` tinyint(4) NOT NULL COMMENT 'Sampling method',
  `de_id` tinyint(1) NOT NULL,
  `de_other` varchar(255) NOT NULL,
  `depth_m` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analytical_method`
--

CREATE TABLE `dct_analytical_method` (
  `id_method` int(11) NOT NULL,
  `am_lod` double DEFAULT 0 COMMENT 'Limit of Detection (LoD)',
  `am_loq` double DEFAULT 0 COMMENT 'Limit of Quantification (LoQ)',
  `am_uncertainty_loq` varchar(255) DEFAULT NULL COMMENT 'Uncertainty at LoQ',
  `dcf_id` tinyint(1) UNSIGNED DEFAULT 0 COMMENT 'Coverage factor',
  `dpm_id` tinyint(1) DEFAULT NULL COMMENT 'Sample preparation method',
  `dpm_other` varchar(255) DEFAULT NULL COMMENT 'Sample preparation method - Other',
  `dam_id` tinyint(1) DEFAULT NULL COMMENT 'Analytical method',
  `dam_other` varchar(255) DEFAULT NULL COMMENT 'Analytical method - Other',
  `dsm_id` tinyint(1) DEFAULT NULL COMMENT 'Has standardised analytical method been used? Code',
  `dsm_other` varchar(255) DEFAULT NULL COMMENT 'Has standardised analytical method been used? Other',
  `am_number` varchar(255) DEFAULT NULL COMMENT 'Has standardised analytical method been used? Number',
  `dp_id` tinyint(1) DEFAULT NULL COMMENT 'Has the used method been validated according to one of the below protocols?',
  `am_corrected_recovery` tinyint(1) DEFAULT NULL COMMENT 'Have the results been corrected for extraction recovery?',
  `am_field_blank` tinyint(1) DEFAULT NULL COMMENT 'Was a field blank checked?',
  `am_iso` tinyint(1) DEFAULT NULL COMMENT 'Is the laboratory accredited according to ISO 17025?',
  `am_given_analyte` tinyint(1) DEFAULT NULL COMMENT 'Is the laboratory accredited for the given analyte?',
  `am_laboratory_participate` tinyint(1) DEFAULT NULL COMMENT 'Does the laboratory participate in interlaboratory studies for the given determinand?',
  `dsp_id` tinyint(1) DEFAULT NULL COMMENT 'Summary of performance of the laboratory in interlaboratory study for the given determinand',
  `am_control_charts` tinyint(1) DEFAULT NULL COMMENT 'Are control charts recorded for the given determinand?',
  `am_internal_standards` tinyint(1) DEFAULT NULL COMMENT 'Nove: Were there used any internal standards or isotopically labelled compounds?',
  `am_authority` tinyint(1) DEFAULT NULL COMMENT 'Are the data controlled by competent authority (apart from accreditation body)?',
  `rating` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rating',
  `am_remark` text DEFAULT NULL COMMENT 'Remark',
  `dsmo_id` tinyint(1) UNSIGNED DEFAULT NULL COMMENT 'Sampling method (Outdoor Air)',
  `dscd_id` tinyint(2) UNSIGNED DEFAULT NULL COMMENT 'Sampling collection device (Outdoor Air)',
  `am_foa` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_analytical_method_20220918`
--

CREATE TABLE `dct_analytical_method_20220918` (
  `id_method` int(11) NOT NULL,
  `am_lod` double NOT NULL COMMENT 'Limit of Detection (LoD)',
  `am_loq` double NOT NULL COMMENT 'Limit of Quantification (LoQ)',
  `am_uncertainty_loq` varchar(255) NOT NULL COMMENT 'Uncertainty at LoQ',
  `dcf_id` tinyint(1) NOT NULL COMMENT 'Coverage factor',
  `dpm_id` tinyint(1) NOT NULL COMMENT 'Sample preparation method',
  `dpm_other` varchar(255) NOT NULL COMMENT 'Sample preparation method - Other',
  `dam_id` tinyint(1) NOT NULL COMMENT 'Analytical method',
  `dam_other` varchar(255) NOT NULL COMMENT 'Analytical method - Other',
  `dsm_id` tinyint(1) NOT NULL COMMENT 'Has standardised analytical method been used? Code',
  `dsm_other` varchar(255) NOT NULL COMMENT 'Has standardised analytical method been used? Other',
  `am_number` varchar(255) NOT NULL COMMENT 'Has standardised analytical method been used? Number',
  `dp_id` tinyint(1) NOT NULL COMMENT 'Has the used method been validated according to one of the below protocols?',
  `am_corrected_recovery` tinyint(1) NOT NULL COMMENT 'Have the results been corrected for extraction recovery?',
  `am_field_blank` tinyint(1) NOT NULL COMMENT 'Was a field blank checked?',
  `am_iso` tinyint(1) NOT NULL COMMENT 'Is the laboratory accredited according to ISO 17025?',
  `am_given_analyte` tinyint(1) NOT NULL COMMENT 'Is the laboratory accredited for the given analyte?',
  `am_laboratory_participate` tinyint(1) NOT NULL COMMENT 'Does the laboratory participate in interlaboratory studies for the given determinand?',
  `dsp_id` tinyint(1) NOT NULL COMMENT 'Summary of performance of the laboratory in interlaboratory study for the given determinand',
  `am_control_charts` tinyint(1) NOT NULL COMMENT 'Are control charts recorded for the given determinand?',
  `am_internal_standards` tinyint(1) NOT NULL COMMENT 'Nove: Were there used any internal standards or isotopically labelled compounds?',
  `am_authority` tinyint(1) NOT NULL COMMENT 'Are the data controlled by competent authority (apart from accreditation body)?',
  `rating` int(10) UNSIGNED NOT NULL COMMENT 'Rating',
  `am_remark` text NOT NULL COMMENT 'Remark',
  `dsmo_id` tinyint(1) UNSIGNED NOT NULL COMMENT 'Sampling method (Outdoor Air)',
  `dscd_id` tinyint(2) UNSIGNED NOT NULL COMMENT 'Sampling collection device (Outdoor Air)'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_count_record`
--

CREATE TABLE `dct_count_record` (
  `numRecords` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_data_source`
--

CREATE TABLE `dct_data_source` (
  `id_data` int(11) NOT NULL,
  `dts_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_other` varchar(255) NOT NULL,
  `title_project` varchar(255) NOT NULL,
  `organisation` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `laboratory_name` varchar(255) NOT NULL DEFAULT '',
  `laboratory_id` varchar(255) NOT NULL DEFAULT '',
  `literature1` text NOT NULL,
  `literature2` text NOT NULL,
  `author` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_data_source_20220829`
--

CREATE TABLE `dct_data_source_20220829` (
  `id_data` int(11) NOT NULL,
  `dts_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_other` varchar(255) NOT NULL,
  `title_project` varchar(255) NOT NULL,
  `organisation` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `laboratory_name` varchar(255) NOT NULL DEFAULT '',
  `laboratory_id` varchar(255) NOT NULL DEFAULT '',
  `literature1` text NOT NULL,
  `literature2` text NOT NULL,
  `author` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_data_source_20220918`
--

CREATE TABLE `dct_data_source_20220918` (
  `id_data` int(11) NOT NULL,
  `dts_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_id` tinyint(1) UNSIGNED NOT NULL,
  `dtm_other` varchar(255) NOT NULL,
  `title_project` varchar(255) NOT NULL,
  `organisation` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `laboratory_name` varchar(255) NOT NULL DEFAULT '',
  `laboratory_id` varchar(255) NOT NULL DEFAULT '',
  `literature1` text NOT NULL,
  `literature2` text NOT NULL,
  `author` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `dct_list`
--

CREATE TABLE `dct_list` (
  `list_id` int(11) NOT NULL,
  `list_file` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_name` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_date` date DEFAULT NULL,
  `list_analysis_from` int(11) DEFAULT NULL,
  `list_analysis_to` int(11) DEFAULT NULL,
  `list_analysis_number` int(11) DEFAULT NULL,
  `list_source_from` int(11) DEFAULT NULL,
  `list_source_to` int(11) DEFAULT NULL,
  `list_source_number` int(11) DEFAULT NULL,
  `list_method_from` int(11) DEFAULT NULL,
  `list_method_to` int(11) DEFAULT NULL,
  `list_method_number` int(11) DEFAULT NULL,
  `list_type` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_note` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matrice_dct` tinyint(4) DEFAULT NULL,
  `list_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `list_common` int(11) DEFAULT NULL,
  `list_protected` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `main_search`
--

CREATE TABLE `main_search` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `com_chemical` tinyint(1) NOT NULL,
  `com_bioassay` tinyint(1) NOT NULL,
  `com_passive` tinyint(1) NOT NULL,
  `com_ecotox` tinyint(1) NOT NULL,
  `com_country_chemical` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `com_country_bioassay` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `com_country_passive` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `main_search_country`
--

CREATE TABLE `main_search_country` (
  `country` char(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `country_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_analytical_method`
--

CREATE TABLE `search_analytical_method` (
  `dam_id` tinyint(2) NOT NULL,
  `dam_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_compound`
--

CREATE TABLE `search_compound` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `sus_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sus_cas` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `StdInChIKey` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_country`
--

CREATE TABLE `search_country` (
  `country` char(2) CHARACTER SET utf8mb4 NOT NULL,
  `country_name` varchar(50) CHARACTER SET utf8mb4 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_laboratory`
--

CREATE TABLE `search_laboratory` (
  `laboratory_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_matrix`
--

CREATE TABLE `search_matrix` (
  `matrice_id` tinyint(4) NOT NULL,
  `matrice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_organisation`
--

CREATE TABLE `search_organisation` (
  `id` int(11) NOT NULL,
  `organisation_name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `search_save`
--

CREATE TABLE `search_save` (
  `user` int(11) NOT NULL,
  `country` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_name` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `matrix` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sus_id` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dcod_id` tinyint(3) UNSIGNED NOT NULL,
  `concentration_value_select` tinyint(3) UNSIGNED NOT NULL,
  `concentration_value` double UNSIGNED NOT NULL,
  `dts_id` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `organisation` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `laboratory_name` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lod_char` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lod` double UNSIGNED NOT NULL,
  `loq_char` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loq` double UNSIGNED NOT NULL,
  `dam_id` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteria` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_from` int(4) NOT NULL,
  `year_to` int(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics1`
--

CREATE TABLE `statistics1` (
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics2`
--

CREATE TABLE `statistics2` (
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics3`
--

CREATE TABLE `statistics3` (
  `sus_id` int(8) UNSIGNED ZEROFILL NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics4`
--

CREATE TABLE `statistics4` (
  `matrice_title1` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics5`
--

CREATE TABLE `statistics5` (
  `matrice_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics6`
--

CREATE TABLE `statistics6` (
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics7`
--

CREATE TABLE `statistics7` (
  `dts_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics8`
--

CREATE TABLE `statistics8` (
  `country_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_country` int(11) NOT NULL,
  `number_compound` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `statistics9`
--

CREATE TABLE `statistics9` (
  `country_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_year` year(4) NOT NULL,
  `number` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Kľúče pre exportované tabuľky
--

--
-- Indexy pre tabuľku `data_addition`
--
ALTER TABLE `data_addition`
  ADD PRIMARY KEY (`dadd_id`);

--
-- Indexy pre tabuľku `data_air_filtration`
--
ALTER TABLE `data_air_filtration`
  ADD PRIMARY KEY (`dairf_id`);

--
-- Indexy pre tabuľku `data_analytical_method`
--
ALTER TABLE `data_analytical_method`
  ADD PRIMARY KEY (`dam_id`);

--
-- Indexy pre tabuľku `data_bioassay`
--
ALTER TABLE `data_bioassay`
  ADD PRIMARY KEY (`dbio_id`);

--
-- Indexy pre tabuľku `data_carrier`
--
ALTER TABLE `data_carrier`
  ADD PRIMARY KEY (`dcar_id`);

--
-- Indexy pre tabuľku `data_category`
--
ALTER TABLE `data_category`
  ADD PRIMARY KEY (`dcat_id`);

--
-- Indexy pre tabuľku `data_category_environment`
--
ALTER TABLE `data_category_environment`
  ADD PRIMARY KEY (`dcenv_id`);

--
-- Indexy pre tabuľku `data_category_enviro_h`
--
ALTER TABLE `data_category_enviro_h`
  ADD PRIMARY KEY (`dcaen_id`);

--
-- Indexy pre tabuľku `data_category_micro_p`
--
ALTER TABLE `data_category_micro_p`
  ADD PRIMARY KEY (`dcamp_id`);

--
-- Indexy pre tabuľku `data_category_micro_s`
--
ALTER TABLE `data_category_micro_s`
  ADD PRIMARY KEY (`dcams_id`);

--
-- Indexy pre tabuľku `data_category_micro_t`
--
ALTER TABLE `data_category_micro_t`
  ADD PRIMARY KEY (`dcamt_id`);

--
-- Indexy pre tabuľku `data_compound`
--
ALTER TABLE `data_compound`
  ADD PRIMARY KEY (`nis_id`),
  ADD KEY `nis_title` (`nis_title`),
  ADD KEY `nis_cas` (`nis_cas`);

--
-- Indexy pre tabuľku `data_concentration_data`
--
ALTER TABLE `data_concentration_data`
  ADD PRIMARY KEY (`dcod_id`);

--
-- Indexy pre tabuľku `data_conditions`
--
ALTER TABLE `data_conditions`
  ADD PRIMARY KEY (`dcon_id`);

--
-- Indexy pre tabuľku `data_country`
--
ALTER TABLE `data_country`
  ADD PRIMARY KEY (`country`);

--
-- Indexy pre tabuľku `data_coverage_factor`
--
ALTER TABLE `data_coverage_factor`
  ADD PRIMARY KEY (`dcf_id`);

--
-- Indexy pre tabuľku `data_cto`
--
ALTER TABLE `data_cto`
  ADD PRIMARY KEY (`dcto_id`);

--
-- Indexy pre tabuľku `data_depth`
--
ALTER TABLE `data_depth`
  ADD PRIMARY KEY (`de_id`);

--
-- Indexy pre tabuľku `data_detector`
--
ALTER TABLE `data_detector`
  ADD PRIMARY KEY (`ddet_id`);

--
-- Indexy pre tabuľku `data_dilution`
--
ALTER TABLE `data_dilution`
  ADD PRIMARY KEY (`ddil_id`);

--
-- Indexy pre tabuľku `data_effect`
--
ALTER TABLE `data_effect`
  ADD PRIMARY KEY (`deff_id`);

--
-- Indexy pre tabuľku `data_effluent_influent`
--
ALTER TABLE `data_effluent_influent`
  ADD PRIMARY KEY (`effluent_influent_id`);

--
-- Indexy pre tabuľku `data_endpoint`
--
ALTER TABLE `data_endpoint`
  ADD PRIMARY KEY (`dend_id`);

--
-- Indexy pre tabuľku `data_exposition`
--
ALTER TABLE `data_exposition`
  ADD PRIMARY KEY (`dext_id`);

--
-- Indexy pre tabuľku `data_field_work`
--
ALTER TABLE `data_field_work`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexy pre tabuľku `data_focus`
--
ALTER TABLE `data_focus`
  ADD PRIMARY KEY (`focus_id`);

--
-- Indexy pre tabuľku `data_fraction`
--
ALTER TABLE `data_fraction`
  ADD PRIMARY KEY (`df_id`);

--
-- Indexy pre tabuľku `data_grain`
--
ALTER TABLE `data_grain`
  ADD PRIMARY KEY (`dgra_id`);

--
-- Indexy pre tabuľku `data_guideline`
--
ALTER TABLE `data_guideline`
  ADD PRIMARY KEY (`dgui_id`);

--
-- Indexy pre tabuľku `data_identification`
--
ALTER TABLE `data_identification`
  ADD PRIMARY KEY (`didea_id`);

--
-- Indexy pre tabuľku `data_identified`
--
ALTER TABLE `data_identified`
  ADD PRIMARY KEY (`dide_id`);

--
-- Indexy pre tabuľku `data_ind_concentration`
--
ALTER TABLE `data_ind_concentration`
  ADD PRIMARY KEY (`dic_id`);

--
-- Indexy pre tabuľku `data_injector`
--
ALTER TABLE `data_injector`
  ADD PRIMARY KEY (`dinj_id`);

--
-- Indexy pre tabuľku `data_level_cooperation`
--
ALTER TABLE `data_level_cooperation`
  ADD PRIMARY KEY (`level_id`);

--
-- Indexy pre tabuľku `data_loc`
--
ALTER TABLE `data_loc`
  ADD PRIMARY KEY (`dloc_id`);

--
-- Indexy pre tabuľku `data_location`
--
ALTER TABLE `data_location`
  ADD PRIMARY KEY (`dloca_id`);

--
-- Indexy pre tabuľku `data_matrice`
--
ALTER TABLE `data_matrice`
  ADD PRIMARY KEY (`matrice_id`),
  ADD UNIQUE KEY `matrice_id1` (`matrice_id1`,`matrice_id2`,`matrice_id3`);

--
-- Indexy pre tabuľku `data_matrice_20240223`
--
ALTER TABLE `data_matrice_20240223`
  ADD PRIMARY KEY (`matrice_id`),
  ADD UNIQUE KEY `matrice_id1` (`matrice_id1`,`matrice_id2`,`matrice_id3`);

--
-- Indexy pre tabuľku `data_matrice_old`
--
ALTER TABLE `data_matrice_old`
  ADD PRIMARY KEY (`matrice_id`),
  ADD UNIQUE KEY `matrice_id1` (`matrice_id1`,`matrice_id2`,`matrice_id3`);

--
-- Indexy pre tabuľku `data_measurement`
--
ALTER TABLE `data_measurement`
  ADD PRIMARY KEY (`dmeas_id`);

--
-- Indexy pre tabuľku `data_organisation`
--
ALTER TABLE `data_organisation`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `data_particle_size`
--
ALTER TABLE `data_particle_size`
  ADD PRIMARY KEY (`dps_id`);

--
-- Indexy pre tabuľku `data_precision`
--
ALTER TABLE `data_precision`
  ADD PRIMARY KEY (`dpre_id`);

--
-- Indexy pre tabuľku `data_precision_coordinates`
--
ALTER TABLE `data_precision_coordinates`
  ADD PRIMARY KEY (`dpc_id`);

--
-- Indexy pre tabuľku `data_preparation_method`
--
ALTER TABLE `data_preparation_method`
  ADD PRIMARY KEY (`dpm_id`);

--
-- Indexy pre tabuľku `data_pressures`
--
ALTER TABLE `data_pressures`
  ADD PRIMARY KEY (`dpr_id`);

--
-- Indexy pre tabuľku `data_pressures1`
--
ALTER TABLE `data_pressures1`
  ADD PRIMARY KEY (`dprs_id`);

--
-- Indexy pre tabuľku `data_pressures_h`
--
ALTER TABLE `data_pressures_h`
  ADD PRIMARY KEY (`dpreh_id`);

--
-- Indexy pre tabuľku `data_pressures_o`
--
ALTER TABLE `data_pressures_o`
  ADD PRIMARY KEY (`dpreo_id`);

--
-- Indexy pre tabuľku `data_pressures_s`
--
ALTER TABLE `data_pressures_s`
  ADD PRIMARY KEY (`dpres_id`);

--
-- Indexy pre tabuľku `data_pressures_t`
--
ALTER TABLE `data_pressures_t`
  ADD PRIMARY KEY (`dpret_id`);

--
-- Indexy pre tabuľku `data_project_primary`
--
ALTER TABLE `data_project_primary`
  ADD PRIMARY KEY (`dpp_id`);

--
-- Indexy pre tabuľku `data_protocols`
--
ALTER TABLE `data_protocols`
  ADD PRIMARY KEY (`dp_id`);

--
-- Indexy pre tabuľku `data_proxy`
--
ALTER TABLE `data_proxy`
  ADD PRIMARY KEY (`dproxy_id`);

--
-- Indexy pre tabuľku `data_reliability`
--
ALTER TABLE `data_reliability`
  ADD PRIMARY KEY (`drel_id`);

--
-- Indexy pre tabuľku `data_sampling_method`
--
ALTER TABLE `data_sampling_method`
  ADD PRIMARY KEY (`dsa_id`);

--
-- Indexy pre tabuľku `data_sampling_technique`
--
ALTER TABLE `data_sampling_technique`
  ADD PRIMARY KEY (`dst_id`);

--
-- Indexy pre tabuľku `data_scd`
--
ALTER TABLE `data_scd`
  ADD PRIMARY KEY (`dscd_id`);

--
-- Indexy pre tabuľku `data_sewage_sludge`
--
ALTER TABLE `data_sewage_sludge`
  ADD PRIMARY KEY (`dss_id`);

--
-- Indexy pre tabuľku `data_smo`
--
ALTER TABLE `data_smo`
  ADD PRIMARY KEY (`dsmo_id`);

--
-- Indexy pre tabuľku `data_species_group`
--
ALTER TABLE `data_species_group`
  ADD PRIMARY KEY (`dsgr_id`);

--
-- Indexy pre tabuľku `data_standardised_method`
--
ALTER TABLE `data_standardised_method`
  ADD PRIMARY KEY (`dsm_id`);

--
-- Indexy pre tabuľku `data_summary_performance`
--
ALTER TABLE `data_summary_performance`
  ADD PRIMARY KEY (`dsp_id`);

--
-- Indexy pre tabuľku `data_tertiary_treatment`
--
ALTER TABLE `data_tertiary_treatment`
  ADD PRIMARY KEY (`dtt_id`);

--
-- Indexy pre tabuľku `data_tissue_element`
--
ALTER TABLE `data_tissue_element`
  ADD PRIMARY KEY (`dtiel_id`);

--
-- Indexy pre tabuľku `data_toxicity`
--
ALTER TABLE `data_toxicity`
  ADD PRIMARY KEY (`dtox_id`);

--
-- Indexy pre tabuľku `data_treatment_plant`
--
ALTER TABLE `data_treatment_plant`
  ADD PRIMARY KEY (`dtp_id`);

--
-- Indexy pre tabuľku `data_type_monitoring`
--
ALTER TABLE `data_type_monitoring`
  ADD PRIMARY KEY (`dtm_id`);

--
-- Indexy pre tabuľku `data_type_organisation`
--
ALTER TABLE `data_type_organisation`
  ADD PRIMARY KEY (`dto_id`);

--
-- Indexy pre tabuľku `data_type_source`
--
ALTER TABLE `data_type_source`
  ADD PRIMARY KEY (`dts_id`);

--
-- Indexy pre tabuľku `data_type_waste`
--
ALTER TABLE `data_type_waste`
  ADD PRIMARY KEY (`dtw_id`);

--
-- Indexy pre tabuľku `data_unit`
--
ALTER TABLE `data_unit`
  ADD PRIMARY KEY (`du_id`);

--
-- Indexy pre tabuľku `data_yes_no_questions`
--
ALTER TABLE `data_yes_no_questions`
  ADD PRIMARY KEY (`dynq_id`);

--
-- Indexy pre tabuľku `dct_analysis`
--
ALTER TABLE `dct_analysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sus_id` (`sus_id`),
  ADD KEY `country` (`country`),
  ADD KEY `dpc_id` (`dpc_id`),
  ADD KEY `matrix` (`matrix`),
  ADD KEY `dcod_id` (`dcod_id`),
  ADD KEY `dic_id` (`dic_id`),
  ADD KEY `dmm_id` (`dmm_id`),
  ADD KEY `dtod_id` (`dtod_id`),
  ADD KEY `id_method` (`id_method`),
  ADD KEY `id_data` (`id_data`),
  ADD KEY `dst_id` (`dst_id`),
  ADD KEY `dtos_id` (`dtos_id`),
  ADD KEY `sampling_date_y` (`sampling_date_y`),
  ADD KEY `analysis_date_y` (`analysis_date_y`),
  ADD KEY `u_jds4` (`u_jds4`,`u_ufz`,`u_lptc`,`u_nl`,`u_connect`,`u_preempt`,`u_dnieper`,`u_6rbmp`);

--
-- Indexy pre tabuľku `dct_analysis_20220907`
--
ALTER TABLE `dct_analysis_20220907`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sus_id` (`sus_id`),
  ADD KEY `country` (`country`),
  ADD KEY `dpc_id` (`dpc_id`),
  ADD KEY `matrix` (`matrix`),
  ADD KEY `dcod_id` (`dcod_id`),
  ADD KEY `dic_id` (`dic_id`),
  ADD KEY `dmm_id` (`dmm_id`),
  ADD KEY `dtod_id` (`dtod_id`),
  ADD KEY `id_method` (`id_method`),
  ADD KEY `id_data` (`id_data`),
  ADD KEY `dst_id` (`dst_id`);

--
-- Indexy pre tabuľku `dct_analysis_20220918`
--
ALTER TABLE `dct_analysis_20220918`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sus_id` (`sus_id`),
  ADD KEY `country` (`country`),
  ADD KEY `dpc_id` (`dpc_id`),
  ADD KEY `matrix` (`matrix`),
  ADD KEY `dcod_id` (`dcod_id`),
  ADD KEY `dic_id` (`dic_id`),
  ADD KEY `dmm_id` (`dmm_id`),
  ADD KEY `dtod_id` (`dtod_id`),
  ADD KEY `id_method` (`id_method`),
  ADD KEY `id_data` (`id_data`),
  ADD KEY `dst_id` (`dst_id`);

--
-- Indexy pre tabuľku `dct_analysis_air_ambient`
--
ALTER TABLE `dct_analysis_air_ambient`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_air_emissions`
--
ALTER TABLE `dct_analysis_air_emissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_air_indoor`
--
ALTER TABLE `dct_analysis_air_indoor`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_air_outdoor`
--
ALTER TABLE `dct_analysis_air_outdoor`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_air_workplace`
--
ALTER TABLE `dct_analysis_air_workplace`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_biota`
--
ALTER TABLE `dct_analysis_biota`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_old`
--
ALTER TABLE `dct_analysis_old`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sus_id` (`sus_id`),
  ADD KEY `country` (`country`),
  ADD KEY `dpc_id` (`dpc_id`),
  ADD KEY `matrix` (`matrix`),
  ADD KEY `dcod_id` (`dcod_id`),
  ADD KEY `dic_id` (`dic_id`),
  ADD KEY `dmm_id` (`dmm_id`),
  ADD KEY `dtod_id` (`dtod_id`),
  ADD KEY `id_method` (`id_method`),
  ADD KEY `id_data` (`id_data`),
  ADD KEY `dst_id` (`dst_id`);

--
-- Indexy pre tabuľku `dct_analysis_sediments`
--
ALTER TABLE `dct_analysis_sediments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dpr_id` (`dpr_id`,`de_id`,`df_id`,`dcat_id`);

--
-- Indexy pre tabuľku `dct_analysis_sewage_sludge`
--
ALTER TABLE `dct_analysis_sewage_sludge`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_soil`
--
ALTER TABLE `dct_analysis_soil`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_spm`
--
ALTER TABLE `dct_analysis_spm`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_water_ground`
--
ALTER TABLE `dct_analysis_water_ground`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_water_surface`
--
ALTER TABLE `dct_analysis_water_surface`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_water_surface_20220918`
--
ALTER TABLE `dct_analysis_water_surface_20220918`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_water_surface_old`
--
ALTER TABLE `dct_analysis_water_surface_old`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analysis_water_waste`
--
ALTER TABLE `dct_analysis_water_waste`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dtw_id` (`dtw_id`),
  ADD KEY `dtp_id` (`dtp_id`),
  ADD KEY `dtt_id` (`dtt_id`),
  ADD KEY `dsa_id` (`dsa_id`),
  ADD KEY `de_id` (`de_id`),
  ADD KEY `dpr_id` (`dpr_id`);

--
-- Indexy pre tabuľku `dct_analysis_water_waste_old`
--
ALTER TABLE `dct_analysis_water_waste_old`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `dct_analytical_method`
--
ALTER TABLE `dct_analytical_method`
  ADD PRIMARY KEY (`id_method`),
  ADD KEY `dcf_id` (`dcf_id`,`dpm_id`,`dam_id`,`dsm_id`,`dp_id`,`dsp_id`,`dsmo_id`,`dscd_id`);

--
-- Indexy pre tabuľku `dct_analytical_method_20220918`
--
ALTER TABLE `dct_analytical_method_20220918`
  ADD PRIMARY KEY (`id_method`);

--
-- Indexy pre tabuľku `dct_data_source`
--
ALTER TABLE `dct_data_source`
  ADD PRIMARY KEY (`id_data`),
  ADD KEY `dts_id` (`dts_id`,`dtm_id`);

--
-- Indexy pre tabuľku `dct_data_source_20220829`
--
ALTER TABLE `dct_data_source_20220829`
  ADD PRIMARY KEY (`id_data`);

--
-- Indexy pre tabuľku `dct_data_source_20220918`
--
ALTER TABLE `dct_data_source_20220918`
  ADD PRIMARY KEY (`id_data`);

--
-- Indexy pre tabuľku `dct_list`
--
ALTER TABLE `dct_list`
  ADD PRIMARY KEY (`list_id`);

--
-- Indexy pre tabuľku `main_search`
--
ALTER TABLE `main_search`
  ADD PRIMARY KEY (`sus_id`);

--
-- Indexy pre tabuľku `main_search_country`
--
ALTER TABLE `main_search_country`
  ADD KEY `country` (`country`),
  ADD KEY `country_name` (`country_name`);

--
-- Indexy pre tabuľku `search_analytical_method`
--
ALTER TABLE `search_analytical_method`
  ADD KEY `dam_id` (`dam_id`,`dam_name`);

--
-- Indexy pre tabuľku `search_country`
--
ALTER TABLE `search_country`
  ADD PRIMARY KEY (`country`),
  ADD KEY `country_name` (`country_name`);

--
-- Indexy pre tabuľku `search_laboratory`
--
ALTER TABLE `search_laboratory`
  ADD KEY `laboratory_name` (`laboratory_name`(250));

--
-- Indexy pre tabuľku `search_matrix`
--
ALTER TABLE `search_matrix`
  ADD KEY `matrice` (`matrice`(250)),
  ADD KEY `matrice_id` (`matrice_id`);

--
-- Indexy pre tabuľku `search_organisation`
--
ALTER TABLE `search_organisation`
  ADD KEY `organisation_name_en` (`organisation_name_en`(250)),
  ADD KEY `id` (`id`);

--
-- Indexy pre tabuľku `search_save`
--
ALTER TABLE `search_save`
  ADD PRIMARY KEY (`user`);

--
-- AUTO_INCREMENT pre exportované tabuľky
--

--
-- AUTO_INCREMENT pre tabuľku `data_addition`
--
ALTER TABLE `data_addition`
  MODIFY `dadd_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_air_filtration`
--
ALTER TABLE `data_air_filtration`
  MODIFY `dairf_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_analytical_method`
--
ALTER TABLE `data_analytical_method`
  MODIFY `dam_id` tinyint(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT pre tabuľku `data_bioassay`
--
ALTER TABLE `data_bioassay`
  MODIFY `dbio_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
--
-- AUTO_INCREMENT pre tabuľku `data_carrier`
--
ALTER TABLE `data_carrier`
  MODIFY `dcar_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_category`
--
ALTER TABLE `data_category`
  MODIFY `dcat_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT pre tabuľku `data_category_environment`
--
ALTER TABLE `data_category_environment`
  MODIFY `dcenv_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pre tabuľku `data_category_enviro_h`
--
ALTER TABLE `data_category_enviro_h`
  MODIFY `dcaen_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_category_micro_p`
--
ALTER TABLE `data_category_micro_p`
  MODIFY `dcamp_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT pre tabuľku `data_category_micro_s`
--
ALTER TABLE `data_category_micro_s`
  MODIFY `dcams_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_category_micro_t`
--
ALTER TABLE `data_category_micro_t`
  MODIFY `dcamt_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pre tabuľku `data_compound`
--
ALTER TABLE `data_compound`
  MODIFY `nis_id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8174;
--
-- AUTO_INCREMENT pre tabuľku `data_concentration_data`
--
ALTER TABLE `data_concentration_data`
  MODIFY `dcod_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pre tabuľku `data_conditions`
--
ALTER TABLE `data_conditions`
  MODIFY `dcon_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_coverage_factor`
--
ALTER TABLE `data_coverage_factor`
  MODIFY `dcf_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_cto`
--
ALTER TABLE `data_cto`
  MODIFY `dcto_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_depth`
--
ALTER TABLE `data_depth`
  MODIFY `de_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pre tabuľku `data_detector`
--
ALTER TABLE `data_detector`
  MODIFY `ddet_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_dilution`
--
ALTER TABLE `data_dilution`
  MODIFY `ddil_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pre tabuľku `data_effect`
--
ALTER TABLE `data_effect`
  MODIFY `deff_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT pre tabuľku `data_effluent_influent`
--
ALTER TABLE `data_effluent_influent`
  MODIFY `effluent_influent_id` tinyint(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_endpoint`
--
ALTER TABLE `data_endpoint`
  MODIFY `dend_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT pre tabuľku `data_exposition`
--
ALTER TABLE `data_exposition`
  MODIFY `dext_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_field_work`
--
ALTER TABLE `data_field_work`
  MODIFY `field_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT pre tabuľku `data_focus`
--
ALTER TABLE `data_focus`
  MODIFY `focus_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT pre tabuľku `data_fraction`
--
ALTER TABLE `data_fraction`
  MODIFY `df_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pre tabuľku `data_grain`
--
ALTER TABLE `data_grain`
  MODIFY `dgra_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_guideline`
--
ALTER TABLE `data_guideline`
  MODIFY `dgui_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT pre tabuľku `data_identification`
--
ALTER TABLE `data_identification`
  MODIFY `didea_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pre tabuľku `data_identified`
--
ALTER TABLE `data_identified`
  MODIFY `dide_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pre tabuľku `data_ind_concentration`
--
ALTER TABLE `data_ind_concentration`
  MODIFY `dic_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_injector`
--
ALTER TABLE `data_injector`
  MODIFY `dinj_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_level_cooperation`
--
ALTER TABLE `data_level_cooperation`
  MODIFY `level_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_loc`
--
ALTER TABLE `data_loc`
  MODIFY `dloc_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_location`
--
ALTER TABLE `data_location`
  MODIFY `dloca_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pre tabuľku `data_matrice`
--
ALTER TABLE `data_matrice`
  MODIFY `matrice_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT pre tabuľku `data_matrice_20240223`
--
ALTER TABLE `data_matrice_20240223`
  MODIFY `matrice_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT pre tabuľku `data_matrice_old`
--
ALTER TABLE `data_matrice_old`
  MODIFY `matrice_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT pre tabuľku `data_measurement`
--
ALTER TABLE `data_measurement`
  MODIFY `dmeas_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_organisation`
--
ALTER TABLE `data_organisation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=406;
--
-- AUTO_INCREMENT pre tabuľku `data_particle_size`
--
ALTER TABLE `data_particle_size`
  MODIFY `dps_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_precision`
--
ALTER TABLE `data_precision`
  MODIFY `dpre_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_precision_coordinates`
--
ALTER TABLE `data_precision_coordinates`
  MODIFY `dpc_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_preparation_method`
--
ALTER TABLE `data_preparation_method`
  MODIFY `dpm_id` tinyint(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures`
--
ALTER TABLE `data_pressures`
  MODIFY `dpr_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures1`
--
ALTER TABLE `data_pressures1`
  MODIFY `dprs_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures_h`
--
ALTER TABLE `data_pressures_h`
  MODIFY `dpreh_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures_o`
--
ALTER TABLE `data_pressures_o`
  MODIFY `dpreo_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures_s`
--
ALTER TABLE `data_pressures_s`
  MODIFY `dpres_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_pressures_t`
--
ALTER TABLE `data_pressures_t`
  MODIFY `dpret_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_project_primary`
--
ALTER TABLE `data_project_primary`
  MODIFY `dpp_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pre tabuľku `data_protocols`
--
ALTER TABLE `data_protocols`
  MODIFY `dp_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_proxy`
--
ALTER TABLE `data_proxy`
  MODIFY `dproxy_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_reliability`
--
ALTER TABLE `data_reliability`
  MODIFY `drel_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_sampling_method`
--
ALTER TABLE `data_sampling_method`
  MODIFY `dsa_id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT pre tabuľku `data_sampling_technique`
--
ALTER TABLE `data_sampling_technique`
  MODIFY `dst_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pre tabuľku `data_scd`
--
ALTER TABLE `data_scd`
  MODIFY `dscd_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
--
-- AUTO_INCREMENT pre tabuľku `data_sewage_sludge`
--
ALTER TABLE `data_sewage_sludge`
  MODIFY `dss_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pre tabuľku `data_smo`
--
ALTER TABLE `data_smo`
  MODIFY `dsmo_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pre tabuľku `data_species_group`
--
ALTER TABLE `data_species_group`
  MODIFY `dsgr_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pre tabuľku `data_standardised_method`
--
ALTER TABLE `data_standardised_method`
  MODIFY `dsm_id` tinyint(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT pre tabuľku `data_summary_performance`
--
ALTER TABLE `data_summary_performance`
  MODIFY `dsp_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_tertiary_treatment`
--
ALTER TABLE `data_tertiary_treatment`
  MODIFY `dtt_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT pre tabuľku `data_tissue_element`
--
ALTER TABLE `data_tissue_element`
  MODIFY `dtiel_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pre tabuľku `data_toxicity`
--
ALTER TABLE `data_toxicity`
  MODIFY `dtox_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pre tabuľku `data_treatment_plant`
--
ALTER TABLE `data_treatment_plant`
  MODIFY `dtp_id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT pre tabuľku `data_type_monitoring`
--
ALTER TABLE `data_type_monitoring`
  MODIFY `dtm_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pre tabuľku `data_type_organisation`
--
ALTER TABLE `data_type_organisation`
  MODIFY `dto_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT pre tabuľku `data_type_source`
--
ALTER TABLE `data_type_source`
  MODIFY `dts_id` tinyint(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_type_waste`
--
ALTER TABLE `data_type_waste`
  MODIFY `dtw_id` tinyint(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `data_unit`
--
ALTER TABLE `data_unit`
  MODIFY `du_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pre tabuľku `data_yes_no_questions`
--
ALTER TABLE `data_yes_no_questions`
  MODIFY `dynq_id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT pre tabuľku `dct_analysis`
--
ALTER TABLE `dct_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161017129;
--
-- AUTO_INCREMENT pre tabuľku `dct_analysis_20220907`
--
ALTER TABLE `dct_analysis_20220907`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27822734;
--
-- AUTO_INCREMENT pre tabuľku `dct_analysis_20220918`
--
ALTER TABLE `dct_analysis_20220918`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27822734;
--
-- AUTO_INCREMENT pre tabuľku `dct_analysis_old`
--
ALTER TABLE `dct_analysis_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pre tabuľku `dct_analytical_method`
--
ALTER TABLE `dct_analytical_method`
  MODIFY `id_method` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158750228;
--
-- AUTO_INCREMENT pre tabuľku `dct_analytical_method_20220918`
--
ALTER TABLE `dct_analytical_method_20220918`
  MODIFY `id_method` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27818578;
--
-- AUTO_INCREMENT pre tabuľku `dct_data_source`
--
ALTER TABLE `dct_data_source`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158751004;
--
-- AUTO_INCREMENT pre tabuľku `dct_data_source_20220829`
--
ALTER TABLE `dct_data_source_20220829`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27818405;
--
-- AUTO_INCREMENT pre tabuľku `dct_data_source_20220918`
--
ALTER TABLE `dct_data_source_20220918`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27818405;
--
-- AUTO_INCREMENT pre tabuľku `dct_list`
--
ALTER TABLE `dct_list`
  MODIFY `list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=414;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
