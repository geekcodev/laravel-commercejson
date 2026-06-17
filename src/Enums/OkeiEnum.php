<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Классификатор единиц измерения по ОКЕИ (ОК 015-94 / МК 002-97)
 * Актуальная редакция — с изменениями 1/97–28/2024 (в силе с 01.12.2024).
 *
 * Содержит все коды из трёх разделов:
 *  - Раздел I:   Международные единицы измерения, включённые в ОКЕИ
 *  - Раздел II:  Национальные единицы измерения, включённые в ОКЕИ
 *  - Раздел III: Четырёхзначные национальные единицы измерения
 *
 * @see https://classifikators.ru/okei
 *
 * @phpstan-immutable
 */
enum OkeiEnum: string implements JsonSerializable
{
    // ═══════════════════════════════════════════════════════════════
    // Раздел I — Международные единицы
    // ═══════════════════════════════════════════════════════════════

    // ─── Единицы длины ─────────────────────────────────────────
    case MILLIMETRE = '003';
    case CENTIMETRE = '004';
    case DECIMETRE = '005';
    case METRE = '006';
    case KILOMETRE = '008';
    case MEGAMETRE = '009';
    case INCH = '039';
    case FOOT = '041';
    case YARD = '043';
    case NAUTICAL_MILE = '047';

    // ─── Единицы площади ───────────────────────────────────────
    case SQUARE_MILLIMETRE = '050';
    case SQUARE_CENTIMETRE = '051';
    case SQUARE_DECIMETRE = '053';
    case SQUARE_METRE = '055';
    case THOUSAND_SQUARE_METRES = '058';
    case HECTARE = '059';
    case SQUARE_KILOMETRE = '061';
    case SQUARE_INCH = '071';
    case SQUARE_FOOT = '073';
    case SQUARE_YARD = '075';
    case ARE = '109';

    // ─── Единицы объёма ────────────────────────────────────────
    case CUBIC_MILLIMETRE = '110';
    case CUBIC_CENTIMETRE = '111';
    case LITRE = '112';
    case CUBIC_METRE = '113';
    case DECILITRE = '118';
    case HECTOLITRE = '122';
    case MEGALITRE = '126';
    case CUBIC_INCH = '131';
    case CUBIC_FOOT = '132';
    case CUBIC_YARD = '133';
    case MILLION_CUBIC_METRES = '159';

    // ─── Единицы массы ─────────────────────────────────────────
    case HECTOGRAM = '160';
    case METRIC_CARAT = '162';
    case GRAM = '163';
    case MICROGRAM = '164';
    case KILOGRAM = '166';
    case TONNE = '168';
    case KILOTONNE = '170';
    case CENTIGRAM = '173';
    case GROSS_REGISTER_TON = '181';
    case MEASUREMENT_TON = '185';
    case QUINTAL = '206';

    // ─── Технические единицы ───────────────────────────────────
    case WATT = '212';
    case KILOWATT = '214';
    case MEGAWATT = '215';
    case VOLT = '222';
    case KILOVOLT = '223';
    case KILOVOLT_AMPERE = '227';
    case MEGAVOLT_AMPERE = '228';
    case KILOVAR = '230';
    case WATT_HOUR = '243';
    case KILOWATT_HOUR = '245';
    case MEGAWATT_HOUR = '246';
    case GIGAWATT_HOUR = '247';
    case AMPERE = '260';
    case AMPERE_HOUR = '263';
    case THOUSAND_AMPERE_HOURS = '264';
    case COULOMB = '270';
    case JOULE = '271';
    case KILOJOULE = '273';
    case OHM = '274';
    case DEGREE_CELSIUS = '280';
    case DEGREE_FAHRENHEIT = '281';
    case CANDELA = '282';
    case LUX = '283';
    case LUMEN = '284';
    case KELVIN = '288';
    case NEWTON = '289';
    case HERTZ = '290';
    case KILOHERTZ = '291';
    case MEGAHERTZ = '292';
    case PASCAL = '294';
    case SIEMENS = '296';
    case KILOPASCAL = '297';
    case MEGAPASCAL = '298';
    case STANDARD_ATMOSPHERE = '300';
    case TECHNICAL_ATMOSPHERE = '301';
    case GIGABECQUEREL = '302';
    case KILOBECQUEREL = '303';
    case MILLICURIE = '304';
    case CURIE = '305';
    case GRAM_FISSILE_ISOTOPES = '306';
    case MEGABECQUEREL = '307';
    case MILLIBAR = '308';
    case BAR = '309';
    case HECTOBAR = '310';
    case KILOBAR = '312';
    case TESLA = '313';
    case FARAD = '314';
    case KILOGRAM_PER_CUBIC_METRE = '316';
    case MOLE = '320';
    case BECQUEREL = '323';
    case WEBER = '324';
    case KNOT = '327';
    case METRE_PER_SECOND = '328';
    case REVOLUTION_PER_SECOND = '330';
    case REVOLUTION_PER_MINUTE = '331';
    case KILOMETRE_PER_HOUR = '333';
    case METRE_PER_SECOND_SQUARED = '335';
    case COULOMB_PER_KILOGRAM = '349';

    // ─── Единицы времени ───────────────────────────────────────
    case SECOND = '354';
    case MINUTE = '355';
    case HOUR = '356';
    case DAY = '359';
    case WEEK = '360';
    case DECADE = '361';
    case MONTH = '362';
    case QUARTER = '364';
    case HALF_YEAR = '365';
    case YEAR = '366';
    case TEN_YEARS = '368';

    // ═══════════════════════════════════════════════════════════════
    // Раздел II — Национальные единицы
    // ═══════════════════════════════════════════════════════════════

    // ─── Единицы длины (национальные) ──────────────────────────
    case NANOMETRE = '015';
    case RUNNING_METRE = '018';
    case THOUSAND_RUNNING_METRES = '019';
    case CONDITIONAL_METRE = '020';
    case THOUSAND_CONDITIONAL_METRES = '048';
    case KILOMETRE_CONDITIONAL_PIPES = '049';

    // ─── Единицы площади (национальные) ────────────────────────
    case THOUSAND_SQUARE_DECIMETRES = '054';
    case MILLION_SQUARE_DECIMETRES = '056';
    case MILLION_SQUARE_METRES = '057';
    case THOUSAND_HECTARES = '060';
    case CONDITIONAL_SQUARE_METRE = '062';
    case THOUSAND_CONDITIONAL_SQ_METRES = '063';
    case MILLION_CONDITIONAL_SQ_METRES = '064';
    case SQUARE_METRE_TOTAL_AREA = '081';
    case THOUSAND_SQ_M_TOTAL_AREA = '082';
    case MILLION_SQ_M_TOTAL_AREA = '083';
    case SQUARE_METRE_LIVING_AREA = '084';
    case THOUSAND_SQ_M_LIVING_AREA = '085';
    case MILLION_SQ_M_LIVING_AREA = '086';
    case SQUARE_METRE_EDUCATIONAL = '087';
    case THOUSAND_SQ_M_EDUCATIONAL = '088';
    case MILLION_SQ_M_TWO_MM = '089';

    // ─── Единицы объёма (национальные) ─────────────────────────
    case THOUSAND_CUBIC_METRES = '114';
    case BILLION_CUBIC_METRES = '115';
    case DECALITRE = '116';
    case THOUSAND_DECALITRES = '119';
    case MILLION_DECALITRES = '120';
    case SOLID_CUBIC_METRE = '121';
    case CONDITIONAL_CUBIC_METRE = '123';
    case THOUSAND_CONDITIONAL_CUBIC_M = '124';
    case MILLION_CUBIC_M_GAS_PROCESSING = '125';
    case THOUSAND_SOLID_CUBIC_METRES = '127';
    case THOUSAND_HALF_LITRES = '128';
    case MILLION_HALF_LITRES = '129';
    case THOUSAND_LITRES = '130';

    // ─── Единицы массы (национальные) ──────────────────────────
    case THOUSAND_METRIC_CARATS = '165';
    case MILLION_METRIC_CARATS = '167';
    case THOUSAND_TONNES = '169';
    case MILLION_TONNES = '171';
    case TONNE_STANDARD_FUEL = '172';
    case THOUSAND_T_STANDARD_FUEL = '175';
    case MILLION_T_STANDARD_FUEL = '176';
    case THOUSAND_T_STORAGE = '177';
    case THOUSAND_T_PROCESSING = '178';
    case CONDITIONAL_TONNE = '179';
    case THOUSAND_QUINTALS = '207';

    // ─── Технические единицы (национальные) ────────────────────
    case VOLT_AMPERE = '226';
    case METRE_PER_HOUR = '231';
    case KILOCALORIE = '232';
    case GIGACALORIE = '233';
    case THOUSAND_GIGACALORIES = '234';
    case MILLION_GIGACALORIES = '235';
    case CALORIE_PER_HOUR = '236';
    case KILOCALORIE_PER_HOUR = '237';
    case GIGACALORIE_PER_HOUR = '238';
    case THOUSAND_GIGACAL_PER_HOUR = '239';
    case MILLION_AMPERE_HOURS = '241';
    case MILLION_KILOVOLT_AMPERE = '242';
    case KILOVOLT_AMPERE_REACTIVE = '248';
    case BILLION_KILOWATT_HOURS = '249';
    case THOUSAND_KVAR = '250';
    case HORSEPOWER = '251';
    case THOUSAND_HORSEPOWER = '252';
    case MILLION_HORSEPOWER = '253';
    case BIT = '254';
    case BYTE = '255';
    case KILOBYTE = '256';
    case MEGABYTE = '257';
    case BAUD = '258';
    case HENRY = '287';
    case KILOGRAM_PER_SQ_CENTIMETRE = '317';
    case MILLIMETRE_WATER_COLUMN = '337';
    case MILLIMETRE_MERCURY = '338';
    case CENTIMETRE_WATER_COLUMN = '339';
    case GRAM_STANDARD_FUEL_PER_KWH = '340';
    case KG_STANDARD_FUEL_PER_GCAL = '341';

    // ─── Единицы времени (национальные) ────────────────────────
    case MICROSECOND = '352';
    case MILLISECOND = '353';

    // ─── Экономические единицы (трёхзначные) ───────────────────
    case ROUBLE = '383';
    case THOUSAND_ROUBLES = '384';
    case MILLION_ROUBLES = '385';
    case BILLION_ROUBLES = '386';
    case TRILLION_ROUBLES = '387';
    case QUADRILLION_ROUBLES = '388';
    case PASSENGER_KILOMETRE = '414';
    case PASSENGER_SEAT = '421';
    case THOUSAND_PASSENGER_KM = '423';
    case MILLION_PASSENGER_KM = '424';
    case FREIGHT_TRAIN_PAIRS_PER_DAY = '426';
    case PASSENGER_TRAFFIC = '427';
    case CUBIC_METRE_KILOMETRE = '428';
    case MILLION_CUBIC_M_KILOMETRE = '430';
    case TAKEOFFS_LANDINGS_PER_HOUR = '431';
    case MILLION_KILOMETRES = '435';
    case TONNE_KILOMETRE = '449';
    case THOUSAND_TONNE_KM = '450';
    case MILLION_TONNE_KM = '451';
    case BILLION_TONNE_KM = '452';
    case THOUSAND_SETS = '479';
    case KILOGRAM_PER_SECOND = '499';
    case THOUSAND_CUBIC_M_PER_HOUR = '508';
    case KILOMETRE_PER_DAY = '509';
    case GRAM_PER_KILOWATT_HOUR = '510';
    case KILOGRAM_PER_GIGACALORIE = '511';
    case TONNE_NUMBER = '512';
    case AUTO_TONNE = '513';
    case TONNE_THRUST = '514';
    case DEADWEIGHT_TONNE = '515';
    case TONNE_TANID = '516';
    case SQ_METRES_PER_PERSON = '518';
    case PERSON_PER_SQ_METRE = '521';
    case PERSON_PER_SQ_KILOMETRE = '522';
    case TONNE_STEAM_PER_HOUR = '533';
    case TONNE_PER_HOUR = '534';
    case TONNE_PER_DAY = '535';
    case TONNE_PER_SHIFT = '536';
    case THOUSAND_TONNES_PER_SEASON = '537';
    case THOUSAND_TONNES_PER_YEAR = '538';
    case MAN_HOUR = '539';
    case MAN_DAY = '540';
    case THOUSAND_MAN_DAYS = '541';
    case THOUSAND_MAN_HOURS = '542';
    case THOUSAND_COND_BANKS_PER_SHIFT = '543';
    case MILLION_UNITS_PER_YEAR = '544';
    case VISITS_PER_SHIFT = '545';
    case THOUSAND_VISITS_PER_SHIFT = '546';
    case PAIRS_PER_SHIFT = '547';
    case THOUSAND_PAIRS_PER_SHIFT = '548';
    case MILLION_TONNES_PER_YEAR = '550';
    case TONNE_PROCESSING_PER_DAY = '552';
    case THOUSAND_T_PROCESSING_PER_DAY = '553';
    case QUINTAL_PROCESSING_PER_DAY = '554';
    case THOUSAND_Q_PROCESSING_PER_DAY = '555';
    case THOUSAND_HEADS_PER_YEAR = '556';
    case MILLION_HEADS_PER_YEAR = '557';
    case THOUSAND_BIRD_PLACES = '558';
    case THOUSAND_HENS = '559';
    case THOUSAND_T_STEAM_PER_HOUR = '561';
    case THOUSAND_SPINDLES = '562';
    case THOUSAND_SPINNING_PLACES = '563';
    case CUBIC_METRE_PER_SECOND = '596';
    case CUBIC_METRE_PER_HOUR = '598';
    case THOUSAND_CUBIC_M_PER_DAY = '599';
    case BOBIN = '616';
    case SHEET = '625';
    case HUNDRED_SHEETS = '626';
    case MILLION_STANDARD_BRICKS = '630';
    case DOSE = '639';
    case THOUSAND_DOSES = '640';
    case DOZEN = '641';
    case UNIT = '642';
    case THOUSAND_UNITS = '643';
    case MILLION_UNITS = '644';
    case ITEM = '657';
    case CHANNEL = '661';
    case THOUSAND_KITS = '673';
    case HUNDRED_BOXES = '683';
    case PLACE = '698';
    case THOUSAND_PLACES = '699';
    case SET = '704';
    case THOUSAND_NUMBERS = '709';
    case PAIR = '715';
    case THOUSAND_HECTARE_PORTIONS = '724';
    case PACK = '728';
    case THOUSAND_PACKS = '729';
    case TWO_DOZENS = '730';
    case TEN_PAIRS = '732';
    case DOZEN_PAIRS = '733';
    case PARCEL = '734';
    case PART = '735';
    case ROLL = '736';
    case DOZEN_ROLLS = '737';
    case DOZEN_PIECES = '740';
    case PERCENT = '744';
    case ELEMENT = '745';
    case PROMILLE = '746';
    case BASIS_POINT = '747';
    case THOUSAND_ROLLS = '751';
    case THOUSAND_MILLS = '761';
    case STATION = '762';
    case THOUSAND_TUBES = '775';
    case THOUSAND_CONDITIONAL_TUBES = '776';
    case PACKAGE = '778';
    case MILLION_PACKAGES = '779';
    case DOZEN_PACKAGES = '780';
    case HUNDRED_PACKAGES = '781';
    case THOUSAND_PACKAGES = '782';
    case PERSON = '792';
    case THOUSAND_PERSONS = '793';
    case MILLION_PERSONS = '794';
    case PIECE = '796';
    case HUNDRED_PIECES = '797';
    case THOUSAND_PIECES = '798';
    case MILLION_PIECES = '799';
    case BILLION_PIECES = '800';
    case TRILLION_PIECES = '801';
    case QUINTILLION_PIECES = '802';
    case MILLION_COPIES = '808';
    case CELL = '810';
    case BOX = '812';
    case ALCOHOL_STRENGTH_MASS = '820';
    case ALCOHOL_STRENGTH_VOLUME = '821';
    case LITRE_PURE_ALCOHOL = '831';
    case HECTOLITRE_PURE_ALCOHOL = '833';
    case HEAD = '836';
    case THOUSAND_PAIRS = '837';
    case MILLION_PAIRS = '838';
    case COMPLETE_SET = '839';
    case SECTION = '840';
    case KG_HYDROGEN_PEROXIDE = '841';
    case KG_90_PCT_DRY_SUBSTANCE = '845';
    case TONNE_90_PCT_DRY_SUBSTANCE = '847';
    case KG_POTASSIUM_OXIDE = '852';
    case KG_POTASSIUM_HYDROXIDE = '859';
    case KG_NITROGEN = '861';
    case KG_SODIUM_HYDROXIDE = '863';
    case KG_PHOSPHORUS_PENTOXIDE = '865';
    case KG_URANIUM = '867';
    case BOTTLE = '868';
    case THOUSAND_BOTTLES = '869';
    case AMPOULE = '870';
    case THOUSAND_AMPOULE = '871';
    case FLACON = '872';
    case THOUSAND_FLACONS = '873';
    case THOUSAND_TUBES_ITEM = '874';
    case THOUSAND_BOXES = '875';
    case CONDITIONAL_UNIT = '876';
    case THOUSAND_CONDITIONAL_UNITS = '877';
    case MILLION_CONDITIONAL_UNITS = '878';
    case CONDITIONAL_PIECE = '879';
    case THOUSAND_CONDITIONAL_PIECES = '880';
    case CONDITIONAL_JAR = '881';
    case THOUSAND_CONDITIONAL_JARS = '882';
    case MILLION_CONDITIONAL_JARS = '883';
    case CONDITIONAL_PIECE_ITEM = '884';
    case THOUSAND_CONDITIONAL_PIECE_ITEM = '885';
    case MILLION_CONDITIONAL_PIECE_ITEM = '886';
    case CONDITIONAL_BOX = '887';
    case THOUSAND_CONDITIONAL_BOXES = '888';
    case CONDITIONAL_COIL = '889';
    case THOUSAND_CONDITIONAL_COILS = '890';
    case CONDITIONAL_TILE = '891';
    case THOUSAND_CONDITIONAL_TILES = '892';
    case CONDITIONAL_BRICK = '893';
    case THOUSAND_CONDITIONAL_BRICKS = '894';
    case MILLION_CONDITIONAL_BRICKS = '895';
    case FAMILY = '896';
    case THOUSAND_FAMILIES = '897';
    case MILLION_FAMILIES = '898';
    case HOUSEHOLD = '899';
    case THOUSAND_HOUSEHOLDS = '900';
    case MILLION_HOUSEHOLDS = '901';
    case STUDENT_PLACE = '902';
    case THOUSAND_STUDENT_PLACES = '903';
    case WORKPLACE = '904';
    case THOUSAND_WORKPLACES = '905';
    case SEAT = '906';
    case THOUSAND_SEATS = '907';
    case ROOM_NUMBER = '908';
    case APARTMENT = '909';
    case THOUSAND_APARTMENTS = '910';
    case BED = '911';
    case THOUSAND_BEDS = '912';
    case BOOK_FUND_VOLUME = '913';
    case THOUSAND_BOOK_FUND_VOLUMES = '914';
    case CONDITIONAL_REPAIR = '915';
    case CONDITIONAL_REPAIR_PER_YEAR = '916';
    case SHIFT = '917';
    case AUTHOR_SHEET = '918';
    case PRINTED_SHEET = '920';
    case PUBLISHING_SHEET = '921';
    case SIGN = '922';
    case WORD = '923';
    case SYMBOL = '924';
    case CONDITIONAL_PIPE = '925';
    case THOUSAND_PLATES = '930';
    case MILLION_DOSES = '937';
    case MILLION_PRINTED_IMPRESSIONS = '949';
    case WAGON_DAY = '950';
    case THOUSAND_WAGON_HOURS = '951';
    case THOUSAND_WAGON_KM = '952';
    case THOUSAND_SEAT_KM = '953';
    case WAGON_DAY_ITEM = '954';
    case THOUSAND_TRAIN_HOURS = '955';
    case THOUSAND_TRAIN_KM = '956';
    case THOUSAND_TONNE_MILES = '957';
    case THOUSAND_PASSENGER_MILES = '958';
    case CAR_DAY = '959';
    case THOUSAND_CAR_TONNE_DAYS = '960';
    case THOUSAND_CAR_HOURS = '961';
    case THOUSAND_CAR_SEAT_DAYS = '962';
    case REDUCED_HOUR = '963';
    case AIRCRAFT_KILOMETRE = '964';
    case THOUSAND_KILOMETRES = '965';
    case THOUSAND_TONNAGE_TRIPS = '966';
    case MILLION_TONNE_MILES = '967';
    case MILLION_PASSENGER_MILES = '968';
    case MILLION_TONNAGE_MILES = '969';
    case MILLION_PASSENGER_SEAT_MILES = '970';
    case FEED_DAY = '971';
    case QUINTAL_FEED_UNITS = '972';
    case THOUSAND_CAR_KM = '973';
    case THOUSAND_TONNAGE_DAYS = '974';
    case SUGAR_DAY = '975';
    case TWENTY_FOOT_EQUIVALENT = '976';
    case CHANNEL_KM = '977';
    case CHANNEL_ENDS = '978';
    case THOUSAND_COPIES = '979';
    case THOUSAND_DOLLARS = '980';
    case THOUSAND_T_FEED_UNITS = '981';
    case MILLION_T_FEED_UNITS = '982';
    case SHIP_DAY = '983';
    case QUINTAL_PER_HECTARE = '984';
    case THOUSAND_HEADS = '985';
    case THOUSAND_COLOUR_IMPRESSIONS = '986';
    case MILLION_COLOUR_IMPRESSIONS = '987';
    case MILLION_CONDITIONAL_TILES = '988';
    case PERSON_PER_HOUR = '989';
    case PASSENGERS_PER_HOUR = '990';
    case PASSENGER_MILE = '991';

    // ═══════════════════════════════════════════════════════════════
    // Раздел III — Четырёхзначные национальные единицы
    // ═══════════════════════════════════════════════════════════════

    // ─── Дозировки лекарственных препаратов ────────────────────
    case IU_BIO_ACTIVITY = '9910';
    case THOUSAND_IU_BIO_ACTIVITY = '9911';
    case MILLION_IU_BIO_ACTIVITY = '9912';
    case IU_BIO_ACTIVITY_PER_GRAM = '9913';
    case THOUSAND_IU_BIO_ACTIVITY_PER_G = '9914';
    case MILLION_IU_BIO_ACTIVITY_PER_G = '9915';
    case IU_BIO_ACTIVITY_PER_ML = '9916';
    case THOUSAND_IU_BIO_ACTIVITY_PER_ML = '9917';
    case MILLION_IU_BIO_ACTIVITY_PER_ML = '9918';
    case UNIT_BIO_ACTIVITY = '9920';
    case UNIT_BIO_ACTIVITY_PER_G = '9921';
    case THOUSAND_UNIT_BIO_ACTIVITY_PER_G = '9922';
    case UNIT_BIO_ACTIVITY_PER_MCL = '9923';
    case UNIT_BIO_ACTIVITY_PER_ML = '9924';
    case THOUSAND_UNIT_BIO_ACTIVITY_PER_ML = '9925';
    case MILLION_UNIT_BIO_ACTIVITY_PER_ML = '9926';
    case UNIT_BIO_ACTIVITY_PER_DAY = '9927';
    case ANTITOXIC_UNIT = '9930';
    case THOUSAND_ANTITOXIC_UNITS = '9931';
    case ANTITRYPTIC_UNIT = '9940';
    case THOUSAND_ANTITRYPTIC_UNITS = '9941';
    case REACTIVITY_INDEX = '9950';
    case REACTIVITY_INDEX_PER_ML = '9951';
    case KBQ_PER_ML = '9960';
    case MBQ_PER_ML = '9961';
    case MBQ_PER_SQ_METRE = '9962';
    case KALLIKREIN_INHIB_UNIT_PER_ML = '9970';
    case THOUSAND_KALLIKREIN_UNIT_PER_ML = '9971';
    case MILLION_CFU = '9980';
    case MILLION_CFU_PER_PACK = '9981';
    case BILLION_CFU = '9982';
    case PROTEOLYTIC_UNIT = '9983';
    case MCG_PER_ML = '9985';
    case MCG_PER_DAY = '9986';
    case MCG_PER_HOUR = '9987';
    case MCG_PER_DOSE = '9988';
    case MILLIMOLE_PER_ML = '9990';
    case MILLIMOLE_PER_LITRE = '9991';

    // ─── Технические единицы (4-значные) ───────────────────────
    case GRAY_PER_SECOND = '2311';
    case GRAY_PER_MINUTE = '2312';
    case GRAY_PER_HOUR = '2313';
    case MICROGRAY_PER_SECOND = '2314';
    case MICROGRAY_PER_HOUR = '2315';
    case MILLIGRAY_PER_HOUR = '2316';
    case SIEVERT_PER_HOUR = '2351';
    case MICROSIEVERT_PER_SECOND = '2352';
    case MICROSIEVERT_PER_HOUR = '2353';
    case MILLISIEVERT_PER_HOUR = '2354';
    case ANGLE_DEGREE = '2355';
    case ANGLE_MINUTE = '2356';
    case ANGLE_SECOND = '2357';
    case BIT_PER_SECOND = '2541';
    case KILOBIT_PER_SECOND = '2543';
    case MEGABIT_PER_SECOND = '2545';
    case GIGABIT_PER_SECOND = '2547';
    case BYTE_PER_SECOND = '2551';
    case GIGABYTE_PER_SECOND = '2552';
    case GIGABYTE = '2553';
    case TERABYTE = '2554';
    case PETABYTE = '2555';
    case EXABYTE = '2556';
    case ZETTABYTE = '2557';
    case YOTTABYTE = '2558';
    case KILOBYTE_PER_SECOND = '2561';
    case MEGABYTE_PER_SECOND = '2571';
    case ERLANG = '2581';
    case DECIBEL = '3135';
    case MAN_SIEVERT = '3181';
    case BECQUEREL_PER_CUBIC_M = '3231';

    // ─── Экономические единицы (4-значные) ─────────────────────
    case ROUBLE_TONNE = '3831';
    case THOUSAND_ROUBLES_PER_PERSON = '3841';
    case CHILD_DAY = '5401';
    case PERSON_PER_YEAR = '5423';
    case VISIT_ITEM = '5451';
    case THOUSAND_NESTS = '5562';
    case UNITS_PER_YEAR = '6421';
    case CALL = '6422';
    case SEED_UNIT = '6423';
    case STRAIN = '6424';
    case SUBSCRIBER = '7923';
    case SPECIMEN = '8361';
    case BOX_ITEM = '8751';
    case MILLION_HECTARES = '9061';
    case BILLION_HECTARES = '9062';
    case BED_DAY = '9111';
    case PATIENT_DAY = '9113';
    case RECORD = '9245';
    case DOCUMENT = '9246';
    case PRINTED_IMPRESSION = '9491';
    case WAGON_HOUR = '9501';
    case MILLION_HEADS = '9557';
    case FLIGHT_HOUR = '9641';
    case SCORE = '9642';
    case MILLION_DOLLARS = '9802';
    case BILLION_DOLLARS = '9803';
    case DOLLAR_PER_TONNE = '9805';
    case MILLION_EURO = '9822';
    case BILLION_EURO = '9823';

    // ═══════════════════════════════════════════════════════════════
    // Данные
    // ═══════════════════════════════════════════════════════════════

    private const DATA = [
        // ── Единицы длины ──────────────────────────────────────
        '003' => ['Миллиметр', 'мм', 'MMT', 'Millimetre'],
        '004' => ['Сантиметр', 'см', 'CMT', 'Centimetre'],
        '005' => ['Дециметр', 'дм', 'DMT', 'Decimetre'],
        '006' => ['Метр', 'м', 'MTR', 'Metre'],
        '008' => ['Километр; тысяча метров', 'км', 'KMT', 'Kilometre'],
        '009' => ['Мегаметр; миллион метров', 'Мм', 'MAM', 'Megametre'],
        '039' => ['Дюйм (25,4 мм)', 'дюйм', 'INH', 'Inch'],
        '041' => ['Фут (0,3048 м)', 'фут', 'FOT', 'Foot'],
        '043' => ['Ярд (0,9144 м)', 'ярд', 'YRD', 'Yard'],
        '047' => ['Морская миля (1852 м)', 'миля', 'NMI', 'Nautical mile'],
        // ── Единицы площади ────────────────────────────────────
        '050' => ['Квадратный миллиметр', 'мм²', 'MMK', 'Square millimetre'],
        '051' => ['Квадратный сантиметр', 'см²', 'CMK', 'Square centimetre'],
        '053' => ['Квадратный дециметр', 'дм²', 'DMK', 'Square decimetre'],
        '055' => ['Квадратный метр', 'м²', 'MTK', 'Square metre'],
        '058' => ['Тысяча квадратных метров', '10³ м²', 'DAA', 'Thousand square metres'],
        '059' => ['Гектар', 'га', 'HAR', 'Hectare'],
        '061' => ['Квадратный километр', 'км²', 'KMK', 'Square kilometre'],
        '071' => ['Квадратный дюйм (645,16 мм²)', 'дюйм²', 'INK', 'Square inch'],
        '073' => ['Квадратный фут (0,092903 м²)', 'фут²', 'FTK', 'Square foot'],
        '075' => ['Квадратный ярд (0,8361274 м²)', 'ярд²', 'YDK', 'Square yard'],
        '109' => ['Ар (100 м²)', 'а', 'ARE', 'Are'],
        // ── Единицы объёма ─────────────────────────────────────
        '110' => ['Кубический миллиметр', 'мм³', 'MMQ', 'Cubic millimetre'],
        '111' => ['Кубический сантиметр; миллилитр', 'см³', 'CMQ', 'Cubic centimetre'],
        '112' => ['Литр; кубический дециметр', 'л', 'LTR', 'Litre'],
        '113' => ['Кубический метр', 'м³', 'MTQ', 'Cubic metre'],
        '118' => ['Децилитр', 'дл', 'DLT', 'Decilitre'],
        '122' => ['Гектолитр', 'гл', 'HLT', 'Hectolitre'],
        '126' => ['Мегалитр', 'Мл', 'MAL', 'Megalitre'],
        '131' => ['Кубический дюйм (16387,1 мм³)', 'дюйм³', 'INQ', 'Cubic inch'],
        '132' => ['Кубический фут (0,02831685 м³)', 'фут³', 'FTQ', 'Cubic foot'],
        '133' => ['Кубический ярд (0,764555 м³)', 'ярд³', 'YDQ', 'Cubic yard'],
        '159' => ['Миллион кубических метров', '10⁶ м³', 'HMQ', 'Million cubic metres'],
        // ── Единицы массы ──────────────────────────────────────
        '160' => ['Гектограмм', 'гг', 'HGM', 'Hectogram'],
        '162' => ['Метрический карат', 'кар', 'CTM', 'Metric carat'],
        '163' => ['Грамм', 'г', 'GRM', 'Gram'],
        '164' => ['Микрограмм', 'мкг', 'MCG', 'Microgram'],
        '166' => ['Килограмм', 'кг', 'KGM', 'Kilogram'],
        '168' => ['Тонна; метрическая тонна (1000 кг)', 'т', 'TNE', 'Tonne'],
        '170' => ['Килотонна', 'кт', 'KTN', 'Kilotonne'],
        '173' => ['Сантиграмм', 'сг', 'CGM', 'Centigram'],
        '181' => ['Брутто-регистровая тонна (2,8316 м³)', 'БРТ', 'GRT', 'Gross registered ton'],
        '185' => ['Грузоподъёмность в метрических тоннах', 'т грп', 'CCT', 'Carrying capacity'],
        '206' => ['Центнер (метрический) (100 кг)', 'ц', 'DTN', 'Quintal'],
        // ── Технические единицы ────────────────────────────────
        '212' => ['Ватт', 'Вт', 'WTT', 'Watt'],
        '214' => ['Киловатт', 'кВт', 'KWT', 'Kilowatt'],
        '215' => ['Мегаватт; тысяча киловатт', 'МВт', 'MAW', 'Megawatt'],
        '222' => ['Вольт', 'В', 'VLT', 'Volt'],
        '223' => ['Киловольт', 'кВ', 'KVT', 'Kilovolt'],
        '227' => ['Киловольт-ампер', 'кВ·А', 'KVA', 'Kilovolt-ampere'],
        '228' => ['Мегавольт-ампер (тысяча киловольт-ампер)', 'МВ·А', 'MVA', 'Megavolt-ampere'],
        '230' => ['Киловар', 'квар', 'KVR', 'Kilovar'],
        '243' => ['Ватт-час', 'Вт·ч', 'WHR', 'Watt-hour'],
        '245' => ['Киловатт-час', 'кВт·ч', 'KWH', 'Kilowatt-hour'],
        '246' => ['Мегаватт-час; 1000 киловатт-часов', 'МВт·ч', 'MWH', 'Megawatt-hour'],
        '247' => ['Гигаватт-час (миллион киловатт-часов)', 'ГВт·ч', 'GWH', 'Gigawatt-hour'],
        '260' => ['Ампер', 'А', 'AMP', 'Ampere'],
        '263' => ['Ампер-час (3,6 кКл)', 'А·ч', 'AMH', 'Ampere-hour'],
        '264' => ['Тысяча ампер-часов', '10³ А·ч', 'TAH', 'Thousand ampere-hours'],
        '270' => ['Кулон', 'Кл', 'COU', 'Coulomb'],
        '271' => ['Джоуль', 'Дж', 'JOU', 'Joule'],
        '273' => ['Килоджоуль', 'кДж', 'KJO', 'Kilojoule'],
        '274' => ['Ом', 'Ом', 'OHM', 'Ohm'],
        '280' => ['Градус Цельсия', '°C', 'CEL', 'Degree Celsius'],
        '281' => ['Градус Фаренгейта', '°F', 'FAN', 'Degree Fahrenheit'],
        '282' => ['Кандела', 'кд', 'CDL', 'Candela'],
        '283' => ['Люкс', 'лк', 'LUX', 'Lux'],
        '284' => ['Люмен', 'лм', 'LUM', 'Lumen'],
        '288' => ['Кельвин', 'K', 'KEL', 'Kelvin'],
        '289' => ['Ньютон', 'Н', 'NEW', 'Newton'],
        '290' => ['Герц', 'Гц', 'HTZ', 'Hertz'],
        '291' => ['Килогерц', 'кГц', 'KHZ', 'Kilohertz'],
        '292' => ['Мегагерц', 'МГц', 'MHZ', 'Megahertz'],
        '294' => ['Паскаль', 'Па', 'PAL', 'Pascal'],
        '296' => ['Сименс', 'См', 'SIE', 'Siemens'],
        '297' => ['Килопаскаль', 'кПа', 'KPA', 'Kilopascal'],
        '298' => ['Мегапаскаль', 'МПа', 'MPA', 'Megapascal'],
        '300' => ['Физическая атмосфера (101325 Па)', 'атм', 'ATM', 'Standard atmosphere'],
        '301' => ['Техническая атмосфера (98066,5 Па)', 'ат', 'ATT', 'Technical atmosphere'],
        '302' => ['Гигабеккерель', 'ГБк', 'GBQ', 'Gigabecquerel'],
        '303' => ['Килобеккерель', 'кБк', 'KBQ', 'Kilobecquerel'],
        '304' => ['Милликюри', 'мКи', 'MCU', 'Millicurie'],
        '305' => ['Кюри', 'Ки', 'CUR', 'Curie'],
        '306' => ['Грамм делящихся изотопов', 'г Д/И', 'GFI', 'Gram fissile isotopes'],
        '307' => ['Мегабеккерель', 'МБк', 'MBQ', 'Megabecquerel'],
        '308' => ['Миллибар', 'мб', 'MBR', 'Millibar'],
        '309' => ['Бар', 'бар', 'BAR', 'Bar'],
        '310' => ['Гектобар', 'гб', 'HBA', 'Hectobar'],
        '312' => ['Килобар', 'кб', 'KBA', 'Kilobar'],
        '313' => ['Тесла', 'Тл', 'TES', 'Tesla'],
        '314' => ['Фарад', 'Ф', 'FAR', 'Farad'],
        '316' => ['Килограмм на кубический метр', 'кг/м³', 'KMQ', 'Kilogram per cubic metre'],
        '320' => ['Моль', 'моль', 'MOL', 'Mole'],
        '323' => ['Беккерель', 'Бк', 'BQL', 'Becquerel'],
        '324' => ['Вебер', 'Вб', 'WEB', 'Weber'],
        '327' => ['Узел (миля/ч)', 'уз', 'KNT', 'Knot'],
        '328' => ['Метр в секунду', 'м/с', 'MTS', 'Metre per second'],
        '330' => ['Оборот в секунду', 'об/с', 'RPS', 'Revolution per second'],
        '331' => ['Оборот в минуту', 'об/мин', 'RPM', 'Revolution per minute'],
        '333' => ['Километр в час', 'км/ч', 'KMH', 'Kilometre per hour'],
        '335' => ['Метр на секунду в квадрате', 'м/с²', 'MSK', 'Metre per second squared'],
        '349' => ['Кулон на килограмм', 'Кл/кг', 'CKG', 'Coulomb per kilogram'],
        // ── Единицы времени ─────────────────────────────────────
        '354' => ['Секунда', 'с', 'SEC', 'Second'],
        '355' => ['Минута', 'мин', 'MIN', 'Minute'],
        '356' => ['Час', 'ч', 'HUR', 'Hour'],
        '359' => ['Сутки', 'сут', 'DAY', 'Day'],
        '360' => ['Неделя', 'нед', 'WEE', 'Week'],
        '361' => ['Декада', 'дек', 'DAD', 'Decade'],
        '362' => ['Месяц', 'мес', 'MON', 'Month'],
        '364' => ['Квартал', 'кварт', 'QAN', 'Quarter'],
        '365' => ['Полугодие', 'полгода', 'SAN', 'Half-year'],
        '366' => ['Год', 'г', 'ANN', 'Year'],
        '368' => ['Десятилетие', 'деслет', 'DEC', 'Ten years'],
        // ══ Раздел II — Национальные единицы ════════════════════
        // ── Единицы длины ──────────────────────────────────────
        '015' => ['Нанометр', 'нм', 'NMT', 'Nanometre'],
        '018' => ['Погонный метр', 'пог. м', 'POG_M', 'Running metre'],
        '019' => ['Тысяча погонных метров', '10³ пог. м', 'THS_POG_M', 'Thousand running metres'],
        '020' => ['Условный метр', 'усл. м', 'USL_M', 'Conditional metre'],
        '048' => ['Тысяча условных метров', '10³ усл. м', 'THS_USL_M', 'Thousand conditional metres'],
        '049' => ['Километр условных труб', 'км усл. труб', 'KM_USL_TR', 'Kilometre conditional pipes'],
        // ── Единицы площади ────────────────────────────────────
        '054' => ['Тысяча квадратных дециметров', '10³ дм²', 'THS_DM2', 'Thousand square decimetres'],
        '056' => ['Миллион квадратных дециметров', '10⁶ дм²', 'MLN_DM2', 'Million square decimetres'],
        '057' => ['Миллион квадратных метров', '10⁶ м²', 'MLN_M2', 'Million square metres'],
        '060' => ['Тысяча гектаров', '10³ га', 'THS_HA', 'Thousand hectares'],
        '062' => ['Условный квадратный метр', 'усл. м²', 'USL_M2', 'Conditional square metre'],
        '063' => ['Тысяча условных квадратных метров', '10³ усл. м²', 'THS_USL_M2', 'Thousand conditional sq metres'],
        '064' => ['Миллион условных квадратных метров', '10⁶ усл. м²', 'MLN_USL_M2', 'Million conditional sq metres'],
        '081' => ['Квадратный метр общей площади', 'м² общ. пл', 'M2_OBSCH', 'Square metre total area'],
        '082' => ['Тысяча квадратных метров общей площади', '10³ м² общ. пл', 'THS_M2_OBSCH', 'Thousand sq m total area'],
        '083' => ['Миллион квадратных метров общей площади', '10⁶ м² общ. пл', 'MLN_M2_OBSCH', 'Million sq m total area'],
        '084' => ['Квадратный метр жилой площади', 'м² жил. пл', 'M2_GIL', 'Square metre living area'],
        '085' => ['Тысяча квадратных метров жилой площади', '10³ м² жил. пл', 'THS_M2_GIL', 'Thousand sq m living area'],
        '086' => ['Миллион квадратных метров жилой площади', '10⁶ м² жил. пл', 'MLN_M2_GIL', 'Million sq m living area'],
        '087' => ['Квадратный метр учебно-лабораторных зданий', 'м² уч. лаб.', 'M2_UCH_LAB', 'Square metre educational'],
        '088' => ['Тысяча квадратных метров учебно-лабораторных зданий', '10³ м² уч. лаб.', 'THS_M2_UCH', 'Thousand sq m educational'],
        '089' => ['Миллион кв. метров в двухмиллиметровом исчислении', '10⁶ м² 2 мм', 'MLN_M2_2MM', 'Million sq m two-mm'],
        // ── Единицы объёма ─────────────────────────────────────
        '114' => ['Тысяча кубических метров', '10³ м³', 'THS_M3', 'Thousand cubic metres'],
        '115' => ['Миллиард кубических метров', '10⁹ м³', 'MLRD_M3', 'Billion cubic metres'],
        '116' => ['Декалитр', 'дкл', 'DKL', 'Decalitre'],
        '119' => ['Тысяча декалитров', '10³ дкл', 'THS_DKL', 'Thousand decalitres'],
        '120' => ['Миллион декалитров', '10⁶ дкл', 'MLN_DKL', 'Million decalitres'],
        '121' => ['Плотный кубический метр', 'плотн. м³', 'PLOTN_M3', 'Solid cubic metre'],
        '123' => ['Условный кубический метр', 'усл. м³', 'USL_M3', 'Conditional cubic metre'],
        '124' => ['Тысяча условных кубических метров', '10³ усл. м³', 'THS_USL_M3', 'Thousand conditional cubic m'],
        '125' => ['Миллион кубических метров переработки газа', '10⁶ м³ перераб. газа', 'MLN_M3_GAZ', 'Million cubic m gas processing'],
        '127' => ['Тысяча плотных кубических метров', '10³ плотн. м³', 'THS_PLOTN', 'Thousand solid cubic metres'],
        '128' => ['Тысяча полулитров', '10³ пол. л', 'THS_POL_L', 'Thousand half-litres'],
        '129' => ['Миллион полулитров', '10⁶ пол. л', 'MLN_POL_L', 'Million half-litres'],
        '130' => ['Тысяча литров; 1000 литров', '10³ л', 'THS_L', 'Thousand litres'],
        // ── Единицы массы ──────────────────────────────────────
        '165' => ['Тысяча каратов метрических', '10³ кар', 'THS_KAR', 'Thousand metric carats'],
        '167' => ['Миллион каратов метрических', '10⁶ кар', 'MLN_KAR', 'Million metric carats'],
        '169' => ['Тысяча тонн', '10³ т', 'THS_T', 'Thousand tonnes'],
        '171' => ['Миллион тонн', '10⁶ т', 'MLN_T', 'Million tonnes'],
        '172' => ['Тонна условного топлива', 'т усл. топл', 'T_USL_TOP', 'Tonne standard fuel'],
        '175' => ['Тысяча тонн условного топлива', '10³ т усл. топл', 'THS_T_USL', 'Thousand t standard fuel'],
        '176' => ['Миллион тонн условного топлива', '10⁶ т усл. топл', 'MLN_T_USL', 'Million t standard fuel'],
        '177' => ['Тысяча тонн единовременного хранения', '10³ т единовр. хран', 'THS_T_HRAN', 'Thousand t storage'],
        '178' => ['Тысяча тонн переработки', '10³ т перераб', 'THS_T_PER', 'Thousand t processing'],
        '179' => ['Условная тонна', 'усл. т', 'USL_T', 'Conditional tonne'],
        '207' => ['Тысяча центнеров', '10³ ц', 'THS_C', 'Thousand quintals'],
        // ── Технические единицы ────────────────────────────────
        '226' => ['Вольт-ампер', 'В·А', 'V_A', 'Volt-ampere'],
        '231' => ['Метр в час', 'м/ч', 'M_CH', 'Metre per hour'],
        '232' => ['Килокалория', 'ккал', 'KKAL', 'Kilocalorie'],
        '233' => ['Гигакалория', 'Гкал', 'GIGAKAL', 'Gigacalorie'],
        '234' => ['Тысяча гигакалорий', '10³ Гкал', 'THS_GGAL', 'Thousand gigacalories'],
        '235' => ['Миллион гигакалорий', '10⁶ Гкал', 'MLN_GGAL', 'Million gigacalories'],
        '236' => ['Калория в час', 'кал/ч', 'KAL_CH', 'Calorie per hour'],
        '237' => ['Килокалория в час', 'ккал/ч', 'KKAL_CH', 'Kilocalorie per hour'],
        '238' => ['Гигакалория в час', 'Гкал/ч', 'GGAL_CH', 'Gigacalorie per hour'],
        '239' => ['Тысяча гигакалорий в час', '10³ Гкал/ч', 'THS_GGAL_CH', 'Thousand gigacalories per hour'],
        '241' => ['Миллион ампер-часов', '10⁶ А·ч', 'MLN_A_CH', 'Million ampere-hours'],
        '242' => ['Миллион киловольт-ампер', '10⁶ кВ·А', 'MLN_KV_A', 'Million kilovolt-ampere'],
        '248' => ['Киловольт-ампер реактивный', 'кВ·А Р', 'KV_A_R', 'Kilovolt-ampere reactive'],
        '249' => ['Миллиард киловатт-часов', '10⁹ кВт·ч', 'MLRD_KVT', 'Billion kilowatt-hours'],
        '250' => ['Тысяча киловольт-ампер реактивных', '10³ кВ·А Р', 'THS_KV_A_R', 'Thousand kvar'],
        '251' => ['Лошадиная сила', 'л. с', 'LS', 'Horsepower'],
        '252' => ['Тысяча лошадиных сил', '10³ л. с', 'THS_LS', 'Thousand horsepower'],
        '253' => ['Миллион лошадиных сил', '10⁶ л. с', 'MLN_LS', 'Million horsepower'],
        '254' => ['Бит', 'бит', 'BIT', 'Bit'],
        '255' => ['Байт', 'байт', 'BAJT', 'Byte'],
        '256' => ['Килобайт', 'кбайт', 'KBAJT', 'Kilobyte'],
        '257' => ['Мегабайт', 'Мбайт', 'MBAJT', 'Megabyte'],
        '258' => ['Бод', 'бод', 'BOD', 'Baud'],
        '287' => ['Генри', 'Гн', 'GN', 'Henry'],
        '317' => ['Килограмм на квадратный сантиметр', 'кг/см²', 'KG_SM2', 'Kilogram per sq centimetre'],
        '337' => ['Миллиметр водяного столба', 'мм вод. ст', 'MM_VOD', 'Millimetre water column'],
        '338' => ['Миллиметр ртутного столба', 'мм рт. ст', 'MM_RT', 'Millimetre mercury'],
        '339' => ['Сантиметр водяного столба', 'см вод. ст', 'SM_VOD', 'Centimetre water column'],
        '340' => ['Грамм условного топлива на киловатт-час', 'г у.т./кВт·ч', 'G_UT_KVT', 'Gram standard fuel per kWh'],
        '341' => ['Килограмм условного топлива на гигакалорию', 'кг у.т./Гкал', 'KG_UT_GGAL', 'Kg standard fuel per Gcal'],
        // ── Единицы времени ────────────────────────────────────
        '352' => ['Микросекунда', 'мкс', 'MKS', 'Microsecond'],
        '353' => ['Миллисекунда', 'млс', 'MLS', 'Millisecond'],
        // ── Экономические единицы ──────────────────────────────
        '383' => ['Рубль', 'руб', 'RUB', 'Rouble'],
        '384' => ['Тысяча рублей', '10³ руб', 'THS_RUB', 'Thousand roubles'],
        '385' => ['Миллион рублей', '10⁶ руб', 'MLN_RUB', 'Million roubles'],
        '386' => ['Миллиард рублей', '10⁹ руб', 'MLRD_RUB', 'Billion roubles'],
        '387' => ['Триллион рублей', '10¹² руб', 'TRL_RUB', 'Trillion roubles'],
        '388' => ['Квадрильон рублей', '10¹⁵ руб', 'KVA_RUB', 'Quadrillion roubles'],
        '414' => ['Пассажиро-километр', 'пасс. км', 'PASS_KM', 'Passenger-kilometre'],
        '421' => ['Пассажирское место (пассажирских мест)', 'пасс. мест', 'PASS_MEST', 'Passenger seat'],
        '423' => ['Тысяча пассажиро-километров', '10³ пасс. км', 'THS_PASS_KM', 'Thousand passenger-km'],
        '424' => ['Миллион пассажиро-километров', '10⁶ пасс. км', 'MLN_PASS_KM', 'Million passenger-km'],
        '426' => ['Пар грузовых поездов в сутки', 'пар груз поезд/сут', 'PAR_GRUZ', 'Freight train pairs per day'],
        '427' => ['Пассажиропоток', 'пасс. поток', 'PASS_POT', 'Passenger traffic'],
        '428' => ['Кубический метр-километр', 'м³·км', 'M3_KM', 'Cubic metre-kilometre'],
        '430' => ['Миллион кубических метров-километров', '10⁶ м³·км', 'MLN_M3_KM', 'Million cubic m-kilometre'],
        '431' => ['Взлётов-посадок в час', 'взлёт. посадок/час', 'VZLET', 'Takeoffs-landings per hour'],
        '435' => ['Миллион километров', '10⁶ км', 'MLN_KM', 'Million kilometres'],
        '449' => ['Тонно-километр', 'т·км', 'T_KM', 'Tonne-kilometre'],
        '450' => ['Тысяча тонно-километров', '10³ т·км', 'THS_T_KM', 'Thousand tonne-km'],
        '451' => ['Миллион тонно-километров', '10⁶ т·км', 'MLN_T_KM', 'Million tonne-km'],
        '452' => ['Миллиард тонно-километров', '10⁹ т·км', 'MLRD_T_KM', 'Billion tonne-km'],
        '479' => ['Тысяча наборов', '10³ набор', 'THS_NAB', 'Thousand sets'],
        '499' => ['Килограмм в секунду', 'кг/с', 'KGS', 'Kilogram per second'],
        '508' => ['Тысяча метров кубических в час', '10³ м³/ч', 'THS_M3_CH', 'Thousand cubic m per hour'],
        '509' => ['Километр в сутки', 'км/сут', 'KM_SUT', 'Kilometre per day'],
        '510' => ['Грамм на киловатт-час', 'г/кВт·ч', 'G_KVT', 'Gram per kilowatt-hour'],
        '511' => ['Килограмм на гигакалорию', 'кг/Гкал', 'KG_GGAL', 'Kilogram per gigacalorie'],
        '512' => ['Тонно-номер', 'т·ном', 'T_NOM', 'Tonne-number'],
        '513' => ['Автотонна', 'авто т', 'AVTO_T', 'Auto-tonne'],
        '514' => ['Тонна тяги', 'т. тяги', 'T_TYAGI', 'Tonne thrust'],
        '515' => ['Дедвейт-тонна', 'дедвейт. т', 'DEDVEJ', 'Deadweight tonne'],
        '516' => ['Тонно-танид', 'т. танид', 'T_TANID', 'Tonne-tanid'],
        '518' => ['Квадратных метров на человека', 'м²/чел', 'M2_CHEL', 'Square metres per person'],
        '521' => ['Человек на квадратный метр', 'чел/м²', 'CHEL_M2', 'Person per square metre'],
        '522' => ['Человек на квадратный километр', 'чел/км²', 'CHEL_KM2', 'Person per square kilometre'],
        '533' => ['Тонна пара в час', 'т пар/ч', 'T_PAR', 'Tonne steam per hour'],
        '534' => ['Тонна в час', 'т/ч', 'T_CH', 'Tonne per hour'],
        '535' => ['Тонна в сутки', 'т/сут', 'T_SUT', 'Tonne per day'],
        '536' => ['Тонна в смену', 'т/смен', 'T_SMEN', 'Tonne per shift'],
        '537' => ['Тысяча тонн в сезон', '10³ т/сез', 'THS_T_SEZ', 'Thousand tonnes per season'],
        '538' => ['Тысяча тонн в год', '10³ т/год', 'THS_T_GOD', 'Thousand tonnes per year'],
        '539' => ['Человеко-час', 'чел. ч', 'CHEL_CH', 'Man-hour'],
        '540' => ['Человеко-день', 'чел. дн', 'CHEL_DN', 'Man-day'],
        '541' => ['Тысяча человеко-дней', '10³ чел. дн', 'THS_CHEL_DN', 'Thousand man-days'],
        '542' => ['Тысяча человеко-часов', '10³ чел. ч', 'THS_CHEL_CH', 'Thousand man-hours'],
        '543' => ['Тысяча условных банок в смену', '10³ усл. банк/смен', 'THS_USL_BANK', 'Thousand cond. banks per shift'],
        '544' => ['Миллион единиц в год', '10⁶ ед/год', 'MLN_ED_GOD', 'Million units per year'],
        '545' => ['Посещение в смену', 'посещ/смен', 'POSESH', 'Visits per shift'],
        '546' => ['Тысяча посещений в смену', '10³ посещ/смен', 'THS_POSESH', 'Thousand visits per shift'],
        '547' => ['Пара в смену', 'пар/смен', 'PAR_SMEN', 'Pairs per shift'],
        '548' => ['Тысяча пар в смену', '10³ пар/смен', 'THS_PAR_SMEN', 'Thousand pairs per shift'],
        '550' => ['Миллион тонн в год', '10⁶ т/год', 'MLN_T_GOD', 'Million tonnes per year'],
        '552' => ['Тонна переработки в сутки', 'т перераб/сут', 'T_PER_SUT', 'Tonne processing per day'],
        '553' => ['Тысяча тонн переработки в сутки', '10³ т перераб/сут', 'THS_T_PER_SUT', 'Thousand t processing per day'],
        '554' => ['Центнер переработки в сутки', 'ц перераб/сут', 'C_PER_SUT', 'Quintal processing per day'],
        '555' => ['Тысяча центнеров переработки в сутки', '10³ ц перераб/сут', 'THS_C_PER_SUT', 'Thousand q processing per day'],
        '556' => ['Тысяча голов в год', '10³ гол/год', 'THS_GOL', 'Thousand heads per year'],
        '557' => ['Миллион голов в год', '10⁶ гол/год', 'MLN_GOL', 'Million heads per year'],
        '558' => ['Тысяча птицемест', '10³ птицемест', 'THS_PTICE', 'Thousand bird places'],
        '559' => ['Тысяча кур-несушек', '10³ кур. несуш', 'THS_KUR', 'Thousand hens'],
        '561' => ['Тысяча тонн пара в час', '10³ т пар/ч', 'THS_T_PAR', 'Thousand t steam per hour'],
        '562' => ['Тысяча прядильных веретён', '10³ пряд. верет', 'THS_VERET', 'Thousand spindles'],
        '563' => ['Тысяча прядильных мест', '10³ пряд. мест', 'THS_PRYAD', 'Thousand spinning places'],
        '596' => ['Кубический метр в секунду', 'м³/с', 'MQS', 'Cubic metre per second'],
        '598' => ['Кубический метр в час', 'м³/ч', 'MQH', 'Cubic metre per hour'],
        '599' => ['Тысяча кубических метров в сутки', '10³ м³/сут', 'TQD', 'Thousand cubic m per day'],
        '616' => ['Бобина', 'боб', 'NBB', 'Bobbin'],
        '625' => ['Лист', 'л.', 'LEF', 'Sheet'],
        '626' => ['Сто листов', '100 л.', 'CLF', 'Hundred sheets'],
        '630' => ['Тысяча стандартных условных кирпичей', 'тыс станд. усл. кирп', 'MBE', 'Thousand standard bricks'],
        '639' => ['Доза', 'доз', 'DOZ', 'Dose'],
        '640' => ['Тысяча доз', '10³ доз', 'THS_DOZ', 'Thousand doses'],
        '641' => ['Дюжина (12 шт.)', 'дюжина', 'DZN', 'Dozen'],
        '642' => ['Единица', 'ед', 'ED', 'Unit'],
        '643' => ['Тысяча единиц', '10³ ед', 'THS_ED', 'Thousand units'],
        '644' => ['Миллион единиц', '10⁶ ед', 'MLN_ED', 'Million units'],
        '657' => ['Изделие', 'изд', 'NAR', 'Item'],
        '661' => ['Канал', 'канал', 'KANAL', 'Channel'],
        '673' => ['Тысяча комплектов', '10³ компл', 'THS_KOM', 'Thousand kits'],
        '683' => ['Сто ящиков', '100 ящ.', 'HBX', 'Hundred boxes'],
        '698' => ['Место', 'мест', 'MEST', 'Place'],
        '699' => ['Тысяча мест', '10³ мест', 'THS_MEST', 'Thousand places'],
        '704' => ['Набор', 'набор', 'SET', 'Set'],
        '709' => ['Тысяча номеров', '10³ ном', 'THS_NOM', 'Thousand numbers'],
        '715' => ['Пара (2 шт.)', 'пар', 'NPR', 'Pair'],
        '724' => ['Тысяча гектаров порций', '10³ га порц', 'THS_GA_POR', 'Thousand hectare portions'],
        '728' => ['Пачка', 'пач', 'PACH', 'Pack'],
        '729' => ['Тысяча пачек', '10³ пач', 'THS_PACH', 'Thousand packs'],
        '730' => ['Два десятка', '20', 'SCO', 'Two dozens'],
        '732' => ['Десять пар', '10 пар', 'TPR', 'Ten pairs'],
        '733' => ['Дюжина пар', 'дюжина пар', 'DPR', 'Dozen pairs'],
        '734' => ['Посылка', 'посыл', 'NPL', 'Parcel'],
        '735' => ['Часть', 'часть', 'NPT', 'Part'],
        '736' => ['Рулон', 'рул', 'RUL', 'Roll'],
        '737' => ['Дюжина рулонов', 'дюжина рул', 'DRL', 'Dozen rolls'],
        '740' => ['Дюжина штук', 'дюжина шт', 'DPC', 'Dozen pieces'],
        '744' => ['Процент', '%', 'PROC', 'Percent'],
        '745' => ['Элемент', 'элем', 'NCL', 'Element'],
        '746' => ['Промилле (0,1 процента)', '‰', 'PROM', 'Promille'],
        '747' => ['Базисный пункт', 'б.п.', 'BP', 'Basis point'],
        '751' => ['Тысяча рулонов', '10³ рул', 'THS_RUL', 'Thousand rolls'],
        '761' => ['Тысяча станов', '10³ стан', 'THS_STAN', 'Thousand mills'],
        '762' => ['Станция', 'станц', 'STANC', 'Station'],
        '775' => ['Тысяча тюбиков', '10³ тюбик', 'THS_TUB', 'Thousand tubes'],
        '776' => ['Тысяча условных тубов', '10³ усл. туб', 'THS_USL_TUB', 'Thousand conditional tubes'],
        '778' => ['Упаковка', 'упак', 'NMP', 'Package'],
        '779' => ['Миллион упаковок', '10⁶ упак', 'MLN_UPAK', 'Million packages'],
        '780' => ['Дюжина упаковок', 'дюжина упак', 'DZP', 'Dozen packages'],
        '781' => ['Сто упаковок', '100 упак', 'CNP', 'Hundred packages'],
        '782' => ['Тысяча упаковок', '10³ упак', 'THS_UPAK', 'Thousand packages'],
        '792' => ['Человек', 'чел', 'CHEL', 'Person'],
        '793' => ['Тысяча человек', '10³ чел', 'THS_CHEL', 'Thousand persons'],
        '794' => ['Миллион человек', '10⁶ чел', 'MLN_CHEL', 'Million persons'],
        '796' => ['Штука', 'шт', 'PCE', 'Piece'],
        '797' => ['Сто штук', '100 шт', 'CEN', 'Hundred pieces'],
        '798' => ['Тысяча штук', 'тыс. шт', 'MIL', 'Thousand pieces'],
        '799' => ['Миллион штук', '10⁶ шт', 'MIO', 'Million pieces'],
        '800' => ['Миллиард штук', '10⁹ шт', 'MLD', 'Billion pieces'],
        '801' => ['Биллион штук (Европа); триллион штук', '10¹² шт', 'BIL', 'Billion pieces (Europe)'],
        '802' => ['Квинтильон штук (Европа)', '10¹⁸ шт', 'TRL', 'Quintillion pieces'],
        '808' => ['Миллион экземпляров', '10⁶ экз', 'MLN_EKZ', 'Million copies'],
        '810' => ['Ячейка', 'яч', 'JACH', 'Cell'],
        '812' => ['Ящик', 'ящ', 'JASH', 'Box'],
        '820' => ['Крепость спирта по массе', '% mds', 'ASM', 'Alcohol strength by mass'],
        '821' => ['Крепость спирта по объёму', '% vol', 'ASV', 'Alcohol strength by volume'],
        '831' => ['Литр чистого (100%) спирта', 'л 100% спирта', 'LPA', 'Litre pure alcohol'],
        '833' => ['Гектолитр чистого (100%) спирта', 'Гл 100% спирта', 'HPA', 'Hectolitre pure alcohol'],
        '836' => ['Голова', 'гол', 'GOL', 'Head'],
        '837' => ['Тысяча пар', '10³ пар', 'THS_PAR', 'Thousand pairs'],
        '838' => ['Миллион пар', '10⁶ пар', 'MLN_PAR', 'Million pairs'],
        '839' => ['Комплект', 'компл', 'KOMPL', 'Complete set'],
        '840' => ['Секция', 'секц', 'SEKC', 'Section'],
        '841' => ['Килограмм пероксида водорода', 'кг H₂O₂', 'KPO', 'Kg hydrogen peroxide'],
        '845' => ['Килограмм 90%-го сухого вещества', 'кг 90% с/в', 'KSD', 'Kg 90% dry substance'],
        '847' => ['Тонна 90%-го сухого вещества', 'т 90% с/в', 'TSD', 'Tonne 90% dry substance'],
        '852' => ['Килограмм оксида калия', 'кг K₂O', 'KPO', 'Kg potassium oxide'],
        '859' => ['Килограмм гидроксида калия', 'кг KOH', 'KPH', 'Kg potassium hydroxide'],
        '861' => ['Килограмм азота', 'кг N', 'KNI', 'Kg nitrogen'],
        '863' => ['Килограмм гидроксида натрия', 'кг NaOH', 'KSH', 'Kg sodium hydroxide'],
        '865' => ['Килограмм пятиокиси фосфора', 'кг P₂O₅', 'KPP', 'Kg phosphorus pentoxide'],
        '867' => ['Килограмм урана', 'кг U', 'KUR', 'Kg uranium'],
        '868' => ['Бутылка', 'бут', 'BUT', 'Bottle'],
        '869' => ['Тысяча бутылок', '10³ бут', 'THS_BUT', 'Thousand bottles'],
        '870' => ['Ампула', 'ампул', 'AMPUL', 'Ampoule'],
        '871' => ['Тысяча ампул', '10³ ампул', 'THS_AMPUL', 'Thousand ampoules'],
        '872' => ['Флакон', 'флак', 'FLAK', 'Flacon'],
        '873' => ['Тысяча флаконов', '10³ флак', 'THS_FLAK', 'Thousand flacons'],
        '874' => ['Тысяча тубов', '10³ туб', 'THS_TUB', 'Thousand tubes (item)'],
        '875' => ['Тысяча коробок', '10³ кор', 'THS_KOR', 'Thousand boxes'],
        '876' => ['Условная единица', 'усл. ед', 'USL_ED', 'Conditional unit'],
        '877' => ['Тысяча условных единиц', '10³ усл. ед', 'THS_USL_ED', 'Thousand conditional units'],
        '878' => ['Миллион условных единиц', '10⁶ усл. ед', 'MLN_USL_ED', 'Million conditional units'],
        '879' => ['Условная штука', 'усл. шт', 'USL_SHT', 'Conditional piece'],
        '880' => ['Тысяча условных штук', '10³ усл. шт', 'THS_USL_SHT', 'Thousand conditional pieces'],
        '881' => ['Условная банка', 'усл. банк', 'USL_BANK', 'Conditional jar'],
        '882' => ['Тысяча условных банок', '10³ усл. банк', 'THS_USL_BANK', 'Thousand conditional jars'],
        '883' => ['Миллион условных банок', '10⁶ усл. банк', 'MLN_USL_BANK', 'Million conditional jars'],
        '884' => ['Условный кусок', 'усл. кус', 'USL_KUS', 'Conditional piece (item)'],
        '885' => ['Тысяча условных кусков', '10³ усл. кус', 'THS_USL_KUS', 'Thousand conditional pieces'],
        '886' => ['Миллион условных кусков', '10⁶ усл. кус', 'MLN_USL_KUS', 'Million conditional pieces'],
        '887' => ['Условный ящик', 'усл. ящ', 'USL_JASH', 'Conditional box'],
        '888' => ['Тысяча условных ящиков', '10³ усл. ящ', 'THS_USL_JASH', 'Thousand conditional boxes'],
        '889' => ['Условная катушка', 'усл. кат', 'USL_KAT', 'Conditional coil'],
        '890' => ['Тысяча условных катушек', '10³ усл. кат', 'THS_USL_KAT', 'Thousand conditional coils'],
        '891' => ['Условная плитка', 'усл. плит', 'USL_PLIT', 'Conditional tile'],
        '892' => ['Тысяча условных плиток', '10³ усл. плит', 'THS_USL_PLIT', 'Thousand conditional tiles'],
        '893' => ['Условный кирпич', 'усл. кирп', 'USL_KIRP', 'Conditional brick'],
        '894' => ['Тысяча условных кирпичей', '10³ усл. кирп', 'THS_USL_KIRP', 'Thousand conditional bricks'],
        '895' => ['Миллион условных кирпичей', '10⁶ усл. кирп', 'MLN_USL_KIRP', 'Million conditional bricks'],
        '896' => ['Семья', 'семей', 'SEMEJ', 'Family'],
        '897' => ['Тысяча семей', '10³ семей', 'THS_SEMEJ', 'Thousand families'],
        '898' => ['Миллион семей', '10⁶ семей', 'MLN_SEMEJ', 'Million families'],
        '899' => ['Домохозяйство', 'домхоз', 'DOMHOZ', 'Household'],
        '900' => ['Тысяча домохозяйств', '10³ домхоз', 'THS_DOMHOZ', 'Thousand households'],
        '901' => ['Миллион домохозяйств', '10⁶ домхоз', 'MLN_DOMHOZ', 'Million households'],
        '902' => ['Ученическое место', 'учен. мест', 'UCHEN_MEST', 'Student place'],
        '903' => ['Тысяча ученических мест', '10³ учен. мест', 'THS_UCHEN', 'Thousand student places'],
        '904' => ['Рабочее место', 'раб. мест', 'RAB_MEST', 'Workplace'],
        '905' => ['Тысяча рабочих мест', '10³ раб. мест', 'THS_RAB_MEST', 'Thousand workplaces'],
        '906' => ['Посадочное место', 'посад. мест', 'POSAD_MEST', 'Seat'],
        '907' => ['Тысяча посадочных мест', '10³ посад. мест', 'THS_POSAD', 'Thousand seats'],
        '908' => ['Номер', 'ном', 'NOM', 'Room number'],
        '909' => ['Квартира', 'кварт', 'KVART', 'Apartment'],
        '910' => ['Тысяча квартир', '10³ кварт', 'THS_KVART', 'Thousand apartments'],
        '911' => ['Койка', 'коек', 'KOEK', 'Bed'],
        '912' => ['Тысяча коек', '10³ коек', 'THS_KOEK', 'Thousand beds'],
        '913' => ['Том книжного фонда', 'том книжн. фонд', 'TOM_KN', 'Book fund volume'],
        '914' => ['Тысяча томов книжного фонда', '10³ том. книжн. фонд', 'THS_TOM_KN', 'Thousand book fund volumes'],
        '915' => ['Условный ремонт', 'усл. рем', 'USL_REM', 'Conditional repair'],
        '916' => ['Условный ремонт в год', 'усл. рем/год', 'USL_REM_GOD', 'Conditional repair per year'],
        '917' => ['Смена', 'смен', 'SMEN', 'Shift'],
        '918' => ['Лист авторский', 'л. авт', 'L_AVT', 'Author sheet'],
        '920' => ['Лист печатный', 'л. печ', 'L_PECH', 'Printed sheet'],
        '921' => ['Лист учётно-издательский', 'л. уч.-изд', 'L_UCH_IZD', 'Publishing sheet'],
        '922' => ['Знак', 'знак', 'ZNAK', 'Sign'],
        '923' => ['Слово', 'слово', 'SLOVO', 'Word'],
        '924' => ['Символ', 'символ', 'SIMVOL', 'Symbol'],
        '925' => ['Условная труба', 'усл. труб', 'USL_TRUB', 'Conditional pipe'],
        '930' => ['Тысяча пластин', '10³ пласт', 'THS_PLAST', 'Thousand plates'],
        '937' => ['Миллион доз', '10⁶ доз', 'MLN_DOZ', 'Million doses'],
        '949' => ['Миллион листов-оттисков', '10⁶ лист. оттиск', 'MLN_LIST', 'Million printed impressions'],
        '950' => ['Вагоно(машино)-день', 'ваг (маш). дн', 'VAG_DN', 'Wagon (car) day'],
        '951' => ['Тысяча вагоно-(машино)-часов', '10³ ваг (маш). ч', 'THS_VAG_CH', 'Thousand wagon hours'],
        '952' => ['Тысяча вагоно-(машино)-километров', '10³ ваг (маш). км', 'THS_VAG_KM', 'Thousand wagon km'],
        '953' => ['Тысяча место-километров', '10³ мест. км', 'THS_MEST_KM', 'Thousand seat-km'],
        '954' => ['Вагоно-сутки', 'ваг. сут', 'VAG_SUT', 'Wagon-day'],
        '955' => ['Тысяча поездо-часов', '10³ поезд. ч', 'THS_POEZD_CH', 'Thousand train hours'],
        '956' => ['Тысяча поездо-километров', '10³ поезд. км', 'THS_POEZD_KM', 'Thousand train km'],
        '957' => ['Тысяча тонно-миль', '10³ т. миль', 'THS_T_MIL', 'Thousand tonne-miles'],
        '958' => ['Тысяча пассажиро-миль', '10³ пасс. миль', 'THS_PASS_MIL', 'Thousand passenger miles'],
        '959' => ['Автомобиле-день', 'автомоб. дн', 'AVTO_DN', 'Car-day'],
        '960' => ['Тысяча автомобиле-тонно-дней', '10³ автомоб. т. дн', 'THS_AVTO_T_DN', 'Thousand car-tonne-days'],
        '961' => ['Тысяча автомобиле-часов', '10³ автомоб. ч', 'THS_AVTO_CH', 'Thousand car hours'],
        '962' => ['Тысяча автомобиле-место-дней', '10³ автомоб. мест. дн', 'THS_AVTO_ME', 'Thousand car-seat-days'],
        '963' => ['Приведённый час', 'привед. ч', 'PRIVED_CH', 'Reduced hour'],
        '964' => ['Самолёто-километр', 'самолёт. км', 'SAM_KM', 'Aircraft kilometre'],
        '965' => ['Тысяча километров', '10³ км', 'THS_KM', 'Thousand kilometres'],
        '966' => ['Тысяча тоннаже-рейсов', '10³ тоннаж. рейс', 'THS_TONN_REJ', 'Thousand tonnage trips'],
        '967' => ['Миллион тонно-миль', '10⁶ т. миль', 'MLN_T_MIL', 'Million tonne-miles'],
        '968' => ['Миллион пассажиро-миль', '10⁶ пасс. миль', 'MLN_PASS_MIL', 'Million passenger miles'],
        '969' => ['Миллион тоннаже-миль', '10⁶ тоннаж. миль', 'MLN_TONN_MIL', 'Million tonnage miles'],
        '970' => ['Миллион пассажиро-место-миль', '10⁶ пасс. мест. миль', 'MLN_PASS_ME', 'Million passenger-seat miles'],
        '971' => ['Кормо-день', 'корм. дн', 'KORM_DN', 'Feed day'],
        '972' => ['Центнер кормовых единиц', 'ц корм ед', 'C_KORM_ED', 'Quintal feed units'],
        '973' => ['Тысяча автомобиле-километров', '10³ автомоб. км', 'THS_AVTO_KM', 'Thousand car-km'],
        '974' => ['Тысяча тоннаже-суток', '10³ тоннаж. сут', 'THS_TONN_SUT', 'Thousand tonnage days'],
        '975' => ['Суго-сутки', 'суго. сут.', 'SUGO_SUT', 'Sugar day'],
        '976' => ['Штук в 20-футовом эквиваленте (ДФЭ)', 'штук в 20-футовом эквиваленте', 'SHT_20_FUT', 'Twenty-foot equivalent unit'],
        '977' => ['Канало-километр', 'канал. км', 'KANAL_KM', 'Channel kilometre'],
        '978' => ['Канало-концы', 'канал. конц', 'KANAL_KON', 'Channel ends'],
        '979' => ['Тысяча экземпляров', '10³ экз', 'THS_EKZ', 'Thousand copies'],
        '980' => ['Тысяча долларов', '10³ доллар', 'THS_DOLL', 'Thousand dollars'],
        '981' => ['Тысяча тонн кормовых единиц', '10³ корм ед', 'THS_KORM_ED', 'Thousand t feed units'],
        '982' => ['Миллион тонн кормовых единиц', '10⁶ корм ед', 'MLN_KORM_ED', 'Million t feed units'],
        '983' => ['Судо-сутки', 'суд. сут', 'SUD_SUT', 'Ship day'],
        '984' => ['Центнеров с гектара', 'ц/га', 'C_GA', 'Quintals per hectare'],
        '985' => ['Тысяча голов', '10³ гол', 'THS_GOL', 'Thousand heads'],
        '986' => ['Тысяча краско-оттисков', '10³ краск. оттиск', 'THS_KRAS', 'Thousand colour impressions'],
        '987' => ['Миллион краско-оттисков', '10⁶ краск. оттиск', 'MLN_KRAS', 'Million colour impressions'],
        '988' => ['Миллион условных плиток', '10⁶ усл. плит', 'MLN_USL_PL', 'Million conditional tiles'],
        '989' => ['Человек в час', 'чел/ч', 'CHEL_CH', 'Person per hour'],
        '990' => ['Пассажиров в час', 'пасс/ч', 'PASS_CH', 'Passengers per hour'],
        '991' => ['Пассажиро-миля', 'пасс. миля', 'PASS_MIL', 'Passenger mile'],
        // ══ Раздел III — 4-значные национальные единицы ═════════
        // ── Дозировки лекарственных препаратов ────────────────
        '9910' => ['Международная единица биологической активности', 'МЕ', 'ME', 'International unit bio activity'],
        '9911' => ['Тысяча международных единиц биологической активности', '10³ МЕ', 'THS_ME', 'Thousand IU bio activity'],
        '9912' => ['Миллион международных единиц биологической активности', '10⁶ МЕ', 'MLN_ME', 'Million IU bio activity'],
        '9913' => ['Международная единица биологической активности на грамм', 'МЕ/г', 'ME_G', 'IU bio activity per gram'],
        '9914' => ['Тысяча международных единиц биологической активности на грамм', '10³ МЕ/г', 'THS_ME_G', 'Thousand IU bio activity per g'],
        '9915' => ['Миллион международных единиц биологической активности на грамм', '10⁶ МЕ/г', 'MLN_ME_G', 'Million IU bio activity per g'],
        '9916' => ['Международная единица биологической активности на миллилитр', 'МЕ/мл', 'ME_ML', 'IU bio activity per ml'],
        '9917' => ['Тысяча международных единиц биологической активности на миллилитр', '10³ МЕ/мл', 'THS_ME_ML', 'Thousand IU bio activity per ml'],
        '9918' => ['Миллион международных единиц биологической активности на миллилитр', '10⁶ МЕ/мл', 'MLN_ME_ML', 'Million IU bio activity per ml'],
        '9920' => ['Единица действия биологической активности', 'ЕД', 'ED', 'Unit bio activity'],
        '9921' => ['Единица биологической активности на грамм', 'ЕД/г', 'ED_G', 'Unit bio activity per g'],
        '9922' => ['Тысяча единиц действия биологической активности на грамм', '10³ ЕД/г', 'THS_ED_G', 'Thousand unit bio activity per g'],
        '9923' => ['Единица действия биологической активности на микролитр', 'ЕД/мкл', 'ED_MKL', 'Unit bio activity per mcl'],
        '9924' => ['Единица действия биологической активности на миллилитр', 'ЕД/мл', 'ED_ML', 'Unit bio activity per ml'],
        '9925' => ['Тысяча единиц действия биологической активности на миллилитр', '10³ ЕД/мл', 'THS_ED_ML', 'Thousand unit bio activity per ml'],
        '9926' => ['Миллион единиц действия биологической активности на миллилитр', '10⁶ ЕД/мл', 'MLN_ED_ML', 'Million unit bio activity per ml'],
        '9927' => ['Единица действия биологической активности в сутки', 'ЕД/сут', 'ED_SUT', 'Unit bio activity per day'],
        '9930' => ['Антитоксическая единица', 'АТЕ', 'ATE', 'Antitoxic unit'],
        '9931' => ['Тысяча антитоксических единиц', '10³ АТЕ', 'THS_ATE', 'Thousand antitoxic units'],
        '9940' => ['Антитрипсиновая единица', 'АТрЕ', 'ATRE', 'Antitryptic unit'],
        '9941' => ['Тысяча антитрипсиновых единиц', '10³ АТрЕ', 'THS_ATRE', 'Thousand antitryptic units'],
        '9950' => ['Индекс Реактивности', 'ИР', 'IR', 'Reactivity index'],
        '9951' => ['Индекс Реактивности на миллилитр', 'ИР/мл', 'IR_ML', 'Reactivity index per ml'],
        '9960' => ['Килобеккерель на миллилитр', 'кБк/мл', 'KBQ_ML', 'Kilobecquerel per ml'],
        '9961' => ['Мегабеккерель на миллилитр', 'МБк/мл', 'MBQ_ML', 'Megabecquerel per ml'],
        '9962' => ['Мегабеккерель на метр квадратный', 'МБк/м²', 'MBQ_M2', 'Megabecquerel per sq metre'],
        '9970' => ['Калликреиновая ингибирующая единица на миллилитр', 'КИЕ/мл', 'KIE_ML', 'Kallikrein inhib. unit per ml'],
        '9971' => ['Тысяча калликреиновых ингибирующих единиц на миллилитр', '10³ КИЕ/мл', 'THS_KIE_ML', 'Thousand kallikrein unit per ml'],
        '9980' => ['Миллион колониеобразующих единиц', '10⁶ КОЕ', 'MLN_KOE', 'Million CFU'],
        '9981' => ['Миллион колониеобразующих единиц на пакет', '10⁶ КОЕ/пак', 'MLN_KOE_PAK', 'Million CFU per pack'],
        '9982' => ['Миллиард колониеобразующих единиц', '10⁹ КОЕ', 'MLRD_KOE', 'Billion CFU'],
        '9983' => ['Протеолитическая единица', 'ПЕ', 'PE', 'Proteolytic unit'],
        '9985' => ['Микрограмм на миллилитр', 'мкг/мл', 'MK_ML', 'Microgram per ml'],
        '9986' => ['Микрограмм в сутки', 'мкг/сут', 'MK_SUT', 'Microgram per day'],
        '9987' => ['Микрограмм в час', 'мкг/ч', 'MK_CH', 'Microgram per hour'],
        '9988' => ['Микрограмм на дозу', 'мкг/доз', 'MK_DOZ', 'Microgram per dose'],
        '9990' => ['Миллимоль на миллилитр', 'ммоль/мл', 'MMOL_ML', 'Millimole per ml'],
        '9991' => ['Миллимоль на литр', 'ммоль/л', 'MMOL_L', 'Millimole per litre'],
        // ── Технические единицы (4-значные) ────────────────────
        '2311' => ['Грей в секунду', 'Гр/с', 'GR_S', 'Gray per second'],
        '2312' => ['Грей в минуту', 'Гр/мин', 'GR_MIN', 'Gray per minute'],
        '2313' => ['Грей в час', 'Гр/ч', 'GR_CH', 'Gray per hour'],
        '2314' => ['Микрогрей в секунду', 'мкГр/с', 'MKGR_S', 'Microgray per second'],
        '2315' => ['Микрогрей в час', 'мкГр/ч', 'MKGR_CH', 'Microgray per hour'],
        '2316' => ['Миллигрей в час', 'мГр/ч', 'MGR_CH', 'Milligray per hour'],
        '2351' => ['Зиверт в час', 'Зв/ч', 'ZV_CH', 'Sievert per hour'],
        '2352' => ['Микрозиверт в секунду', 'мкЗв/с', 'MKZV_S', 'Microsievert per second'],
        '2353' => ['Микрозиверт в час', 'мкЗв/ч', 'MKZV_CH', 'Microsievert per hour'],
        '2354' => ['Миллизиверт в час', 'мЗв/ч', 'MZV_CH', 'Millisievert per hour'],
        '2355' => ['Градус (плоского угла)', '°', 'GRAD', 'Degree (plane angle)'],
        '2356' => ['Минута (плоского угла)', '\'', 'MIN_UG', 'Minute (plane angle)'],
        '2357' => ['Секунда (плоского угла)', '"', 'SEC_UG', 'Second (plane angle)'],
        '2541' => ['Бит в секунду', 'бит/с', 'BIT_S', 'Bit per second'],
        '2543' => ['Килобит в секунду', 'кбит/с', 'KBIT_S', 'Kilobit per second'],
        '2545' => ['Мегабит в секунду', 'Мбит/с', 'MBIT_S', 'Megabit per second'],
        '2547' => ['Гигабит в секунду', 'Гбит/с', 'GBIT_S', 'Gigabit per second'],
        '2551' => ['Байт в секунду', 'Байт/с', 'BAJT_S', 'Byte per second'],
        '2552' => ['Гигабайт в секунду', 'ГБ/с', 'GB_S', 'Gigabyte per second'],
        '2553' => ['Гигабайт', 'ГБ', 'GB', 'Gigabyte'],
        '2554' => ['Терабайт', 'ТБ', 'TB', 'Terabyte'],
        '2555' => ['Петабайт', 'ПБ', 'PB', 'Petabyte'],
        '2556' => ['Эксабайт', 'ЭБ', 'EB', 'Exabyte'],
        '2557' => ['Зеттабайт', 'ЗБ', 'ZB', 'Zettabyte'],
        '2558' => ['Йоттабайт', 'ЙБ', 'YB', 'Yottabyte'],
        '2561' => ['Килобайт в секунду', 'кБ/с', 'KB_S', 'Kilobyte per second'],
        '2571' => ['Мегабайт в секунду', 'МБ/с', 'MB_S', 'Megabyte per second'],
        '2581' => ['Эрланг', 'Эрл', 'ERL', 'Erlang'],
        '3135' => ['Децибел', 'дБ', 'DB', 'Decibel'],
        '3181' => ['Человеко-зиверт', 'чел·Зв', 'CHEL_ZV', 'Man-sievert'],
        '3231' => ['Беккерель на метр кубический', 'Бк/м³', 'BK_M3', 'Becquerel per cubic metre'],
        // ── Экономические единицы (4-значные) ───────────────────
        '3831' => ['Рубль тонна', 'руб. т', 'RUB_T', 'Rouble tonne'],
        '3841' => ['Тысяча рублей на человека', '10³ руб/чел', 'THS_RUB_CHEL', 'Thousand roubles per person'],
        '5401' => ['Дето-день', 'дет. дн', 'DET_DN', 'Child-day'],
        '5423' => ['Человек в год', 'чел/год', 'CHEL_GOD', 'Person per year'],
        '5451' => ['Посещение', 'посещ', 'POSESH', 'Visit'],
        '5562' => ['Тысяча гнёзд', '10³ гнёзд', 'THS_GNEZD', 'Thousand nests'],
        '6421' => ['Единиц в год', 'ед/год', 'ED_GOD', 'Units per year'],
        '6422' => ['Вызов', 'выз', 'VIZOV', 'Call'],
        '6423' => ['Посевная единица', 'пос. ед', 'POS_ED', 'Seed unit'],
        '6424' => ['Штамм', 'штамм', 'STAM', 'Strain'],
        '7923' => ['Абонент', 'абон', 'ABON', 'Subscriber'],
        '8361' => ['Особь', 'особь', 'OSOB', 'Specimen'],
        '8751' => ['Коробка', 'короб', 'KOROB', 'Box (item)'],
        '9061' => ['Миллион гектаров', '10⁶ га', 'MLN_GA', 'Million hectares'],
        '9062' => ['Миллиард гектаров', '10⁹ га', 'MLRD_GA', 'Billion hectares'],
        '9111' => ['Койко-день', 'к. дн', 'K_DN', 'Bed-day'],
        '9113' => ['Пациенто-день', 'пац. дн', 'PAC_DN', 'Patient-day'],
        '9245' => ['Запись', 'зап', 'ZAP', 'Record'],
        '9246' => ['Документ', 'док', 'DOK', 'Document'],
        '9491' => ['Лист-оттиск', 'лист. отт', 'LIST_OTT', 'Printed impression'],
        '9501' => ['Вагоно(машино)-час', 'ваг (маш). ч', 'VAG_CH', 'Wagon (car) hour'],
        '9557' => ['Миллион голов', '10⁶ гол', 'MLN_GOL', 'Million heads'],
        '9641' => ['Лётный час', 'лет. ч', 'LET_CH', 'Flight hour'],
        '9642' => ['Балл', 'балл', 'BALL', 'Score'],
        '9802' => ['Миллион долларов', '10⁶ доллар', 'MLN_DOLL', 'Million dollars'],
        '9803' => ['Миллиард долларов', '10⁹ доллар', 'MLRD_DOLL', 'Billion dollars'],
        '9805' => ['Доллар за тонну', 'доллар/т', 'DOLL_T', 'Dollar per tonne'],
        '9822' => ['Миллион евро', '10⁶ евро', 'MLN_EVRO', 'Million euro'],
        '9823' => ['Миллиард евро', '10⁹ евро', 'MLRD_EVRO', 'Billion euro'],
    ];

    // ═══════════════════════════════════════════════════════════════
    // Методы
    // ═══════════════════════════════════════════════════════════════

    public function getCode(): string
    {
        return $this->value;
    }

    public function getFullName(): string
    {
        return self::DATA[$this->value][0];
    }

    public function getShortName(): string
    {
        return self::DATA[$this->value][1];
    }

    public function getInternational(): string
    {
        return self::DATA[$this->value][2];
    }

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match (strtolower($locale)) {
            'ru' => $this->getFullName(),
            'en' => self::DATA[$this->value][3],
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    /**
     * Преобразует enum в структуру, ожидаемую OpenAPI схемой `Unit`
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'full_name' => $this->getFullName(),
            'short_name' => $this->getShortName(),
            'international' => $this->getInternational(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromCode(string $code): self
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        $unit = self::tryFrom($code);

        if ($unit === null) {
            throw new InvalidArgumentException("Invalid OKEI unit code: '{$code}'");
        }

        return $unit;
    }

    public static function tryFromCode(string $code): ?self
    {
        $code = preg_replace('/[^0-9]/', '', $code);

        return self::tryFrom($code);
    }

    public static function isValidCode(string $code): bool
    {
        return self::tryFromCode($code) !== null;
    }
}
