<?php
    /*item = 選項名，sql = 搜尋條件，sql_cd = 附加搜尋條件*/
    $bookSearchItem = array(
        array('item'=>"全部", 'sql'=>'CONCAT_WS(",", `bNb`, `bNm`, `bAt`, `bIl`, `bTr`, `bPb`, `bCn`, `bNt`)'),
        array('item'=>"書號", 'sql'=>'`bNb`'),
        array('item'=>"書名", 'sql'=>'`bNm`'),
        array('item'=>"作者", 'sql'=>'`bAt`'),
        array('item'=>"繪者", 'sql'=>'`bIl`'),
        array('item'=>"譯者", 'sql'=>'`bTr`'),
        array('item'=>"作者＋繪者＋譯者", 'sql'=>'CONCAT_WS(",", `bAt`, `bIl`, `bTr`)'),
        array('item'=>"出版者", 'sql'=>'`bPb`'),
        array('item'=>"櫃號", 'sql'=>'`bCn`'),
        array('item'=>"備註", 'sql'=>'`bNt`')
    );
    $memberSearchItem = array(
        array('item'=>"全部", 'str'=>'all', 'sql'=>'CONCAT_WS(",", `mNb`, `mNbNt`, `mNm`, `mNmNt`, `mAn`, `mPm`, `mGd`, `mBd`, `mJd`, `mAh1`, `mAh1Nt`, `mAh2`, `mAh2Nt`, `mZc1`, `mAd1`, `mAd1Nt`, `mZc2`, `mAd2`, `mAd2Nt`, `mHn1`, `mHn2`, `mOn1`, `mOn1Nt`, `mOn2`, `mOn2Nt`, `mFn`, `mPn1`, `mPn2`, `mEa1`, `mEa1Nt`, `mEa2`, `mEa2Nt`, `mEa3`, `mEa3Nt`, `mNt`, `mPd`)', 'sql_cd'=>''),
        array('item'=>"會號", 'str'=>'num', 'sql'=>'`mNb`', 'sql_cd'=>''),
        array('item'=>"姓名(含別名、筆名)", 'str'=>'name', 'sql'=>'CONCAT_WS(",", `mNm`, `mNmNt`, `mAn`, `mPm`)', 'sql_cd'=>''),
        array('item'=>"生日", 'str'=>'bd', 'sql'=>'`mBd`', 'sql_cd'=>''),
        array('item'=>"生日(年)", 'str'=>'bdy', 'sql'=>'YEAR(`mBd`)', 'sql_cd'=>' AND (`mBdFg` != \'noYear\' OR `mBdFg` IS NULL)'),
        array('item'=>"生日(月)", 'str'=>'bdm', 'sql'=>'CONCAT(LPAD(MONTH(`mBd`), 2, 0))', 'sql_cd'=>''),
        array('item'=>"生日(月日)", 'str'=>'bdd', 'sql'=>'CONCAT(LPAD(MONTH(`mBd`), 2, 0), \'-\', LPAD(DAYOFMONTH(`mBd`), 2, 0))', 'sql_cd'=>' AND (`mBdFg` != \'noDay\' OR `mBdFg` IS NULL)'),
        array('item'=>"籍貫", 'str'=>'ah', 'sql'=>'CONCAT_WS(",", `mAh1`, `mAh1Nt`, `mAh2`, `mAh2Nt`)', 'sql_cd'=>''),
        array('item'=>"地址(含郵遞區號)", 'str'=>'addr', 'sql'=>'CONCAT_WS(",", `mZc1`, `mAd1`, `mAd1Nt`, `mZc2`, `mAd2`, `mAd2Nt`)', 'sql_cd'=>''),
        array('item'=>"電話(含傳真)", 'str'=>'tel', 'sql'=>'CONCAT_WS(",", `mHn1`, `mHn2`, `mOn1`, `mOn1Nt`, `mOn2`, `mOn2Nt`, `mFn`)', 'sql_cd'=>''),
        array('item'=>"手機", 'str'=>'phone', 'sql'=>'CONCAT_WS(",", `mPn1`, `mPn2`)', 'sql_cd'=>''),
        array('item'=>"Email", 'str'=>'email', 'sql'=>'CONCAT_WS(",", `mEa1`, `mEa1Nt`, `mEa2`, `mEa2Nt`, `mEa3`, `mEa3Nt`)', 'sql_cd'=>''),
        array('item'=>"備註", 'str'=>'notes', 'sql'=>'`mNt`', 'sql_cd'=>''),
        array('item'=>"入會日期", 'str'=>'jd'),                                     //輸入字串為「年-月-日」完整格式時，sql 搜尋字串和附加搜尋條件須另外處理
        array('item'=>"會籍到期日期", 'str'=>'lpd', 'sql'=>'`mPd`', 'sql_cd'=>''),
        array('item'=>"榮譽會員", 'str'=>'hnm', 'sql'=>'`mHm` = 1', 'sql_cd'=>''),
        array('item'=>"非榮譽會員", 'str'=>'nhm', 'sql'=>'`mHm` = 0', 'sql_cd'=>''),
        array('item'=>"已故會員", 'str'=>'ddm', 'sql'=>'`mDm` = 1', 'sql_cd'=>'')
    );
    $societySearchItem = array(
        array('item'=>"全部", 'str'=>'all', 'sql'=>'CONCAT_WS(",", `sNb`, `sNbOd`, `sNm`, `sFs`, `sUc`, `sZc`, `sAd`, `sOn1`, `sOn2`, `sFn`, `sPcNm`, `sPcTt`, `sPcNt`, `sRpNm`, `sRpTt`, `sRpNt`, `sRpEx`, `sRpPn`, `sRpEa`, `sLs1Nm`, `sLs1Tt`, `sLs1Nt`, `sLs1Ex`, `sLs1Pn`, `sLs1Ea`, `sLs2Nm`, `sLs2Tt`, `sLs2Nt`, `sLs2Ex`, `sLs2Pn`, `sLs2Ea`, `sLs3Nm`, `sLs3Tt`, `sLs3Nt`, `sLs3Ex`, `sLs3Pn`, `sLs3Ea`, `sJd`, `sNt`, `sPd`)', 'sql_cd'=>''),
        array('item'=>"會號", 'str'=>'num', 'sql'=>'`sNb`', 'sql_cd'=>''),
        array('item'=>"名稱", 'str'=>'name', 'sql'=>'`sNm`', 'sql_cd'=>''),
        array('item'=>"簡稱", 'str'=>'sname', 'sql'=>'`sFs`', 'sql_cd'=>''),
        array('item'=>"統編", 'str'=>'ucode', 'sql'=>'`sUc`', 'sql_cd'=>''),
        array('item'=>"地址(含郵遞區號)", 'str'=>'addr', 'sql'=>'CONCAT_WS(",", `sZc`, `sAd`)', 'sql_cd'=>''),
        array('item'=>"電話(含分機、傳真)", 'str'=>'tel', 'sql'=>'CONCAT_WS(",", `sOn1`, `sOn2`, `sFn`, `sRpEx`, `sLs1Ex`, `sLs2Ex`, `sLs3Ex`)', 'sql_cd'=>''),
        array('item'=>"負責人", 'str'=>'pic', 'sql'=>'CONCAT_WS(",", `sPcNm`, `sPcTt`, `sPcNt`)', 'sql_cd'=>''),
        array('item'=>"代表人", 'str'=>'rps', 'sql'=>'CONCAT_WS(",", `sRpNm`, `sRpTt`, `sRpNt`)', 'sql_cd'=>''),
        array('item'=>"聯絡人", 'str'=>'lis', 'sql'=>'CONCAT_WS(",", `sLs1Nm`, `sLs1Tt`, `sLs1Nt`, `sLs2Nm`, `sLs2Tt`, `sLs2Nt`, `sLs3Nm`, `sLs3Tt`, `sLs3Nt`)', 'sql_cd'=>''),
        array('item'=>"手機", 'str'=>'phone', 'sql'=>'CONCAT_WS(",", `sRpPn`, `sLs1Pn`, `sLs2Pn`, `sLs3Pn`)', 'sql_cd'=>''),
        array('item'=>"Email", 'str'=>'email', 'sql'=>'CONCAT_WS(",", `sRpEa`, `sLs1Ea`, `sLs2Ea`, `sLs3Ea`)', 'sql_cd'=>''),
        array('item'=>"備註", 'str'=>'notes', 'sql'=>'`sNt`', 'sql_cd'=>''),
        array('item'=>"入會日期", 'str'=>'jd'),                                     //輸入字串為「年-月-日」完整格式時，sql 搜尋字串和附加搜尋條件須另外處理
        array('item'=>"會籍到期日期", 'str'=>'lpd', 'sql'=>'`sPd`', 'sql_cd'=>'')
    );
    $circulationSearchItem = array(
        array('item'=>'全部', 'str'=>'all', 'sql'=>'CONCAT_WS(",", `cBn`, `cBm`, `cMn`, `cMm`, `cBt`, `cRt`)'),
        array('item'=>'書號', 'str'=>'bnum', 'sql'=>'`cBn`'),
        array('item'=>'書名', 'str'=>'bname', 'sql'=>'`cBm`'),
        array('item'=>'會號', 'str'=>'mnum', 'sql'=>'`cMn`'),
        array('item'=>'會員姓名', 'str'=>'mname', 'sql'=>'`cMm`')
    );
?>