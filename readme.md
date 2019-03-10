 # 圖書及會員資訊管理系統
 1. 本系統係為[中華民國兒童文學學會](https://www.facebook.com/%E4%B8%AD%E8%8F%AF%E6%B0%91%E5%9C%8B%E5%85%92%E7%AB%A5%E6%96%87%E5%AD%B8%E5%AD%B8%E6%9C%83-177251442370991/)開發，僅在該會內部網路中運行，主要用於處理日常借還書工作，並有會員資料、館藏圖書資料的管理功能。
 2. 因個資關係，暫不提供資料庫檔案。
 3. 程式截圖環境：Mac OS 10.13.5, Google Chrome 67.0.3396.87 (64位元), 解析度 = 1280 × 646。
 
 ## 首頁
 + 顯示系統名稱、功能選擇鈕、現在時間、過期及快到期的借還書紀錄。
 + 右上角可登入管理員身分，登入後才可瀏覽、編輯各管理頁面，否則圖書管理及借閱的頁面只能純瀏覽，會員資料管理頁面則無法瀏覽。
 + 登入後，閒置 2 小時沒有動作，會自動登出。
 + 點選任一行過期及快到期紀錄可連到該名會員的借還書查詢頁面。
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/homepage.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/homepage_soon_due.png)
 
 ## 圖書資料管理頁面
 + 顯示圖書建檔資訊，並可搜尋、新增、修改、刪除（僅為除帳，不從資料庫中刪除）。
 + 新增、修改、刪除的輸入框採 modal box，可點按右上角的「×」、最下方的「取消」按鈕或按鍵盤 ESC 鍵不送資料離開。
 + 點選書名或書號可連到該本書的借還書查詢頁面。
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/book_cursor.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/book_select_page.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/book_add.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/book_modify.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/book_delete.png)
 
 ## 會員資料管理頁面
 + 顯示會員資訊，並可搜尋、新增、修改、刪除。
 + 為配合該學會早期會員資料建置的混亂，這裡的新增、修改欄位較客製化，比其他介面複雜一些。
 + 「姓名」及「生日」欄位旁有兩個預設隱藏的欄位，分別顯示「筆名」、「籍貫」，可點擊切換顯示或隱藏。
 + 新增、修改、刪除的輸入框採 modal box，可點按右上角的「×」、最下方的「取消」按鈕或按鍵盤 ESC 鍵不送資料離開。
 + 點選會員號或會員姓名可連到該會員的借還書紀錄查詢頁面。
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/member_cursor.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/member_add.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/member_modify.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/member_delete.png)
 
 ## 圖書借閱及歸還管理頁面
 + 顯示借還書紀錄，並可搜尋、新增、修改、刪除。
 + 使用 ajax 即時處理還書工作（點按還書按鈕後，免重新整理立即呈現為已還書狀態）。
 + 新增、修改、刪除的輸入框採 modal box，可點按右上角的「×」、最下方的「取消」按鈕或按鍵盤 ESC 鍵不送資料離開。
 + 點選書號或書名可連到該書的圖書資料查詢頁面，點選會員號或會員姓名可連到該會員的個人資料查詢頁面。
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation_add.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation_delete.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation_modify.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation_return_before.png)
 ![image](https://github.com/Wujidadi/SCLROC_BMmanage/blob/master/screen/circulation_return_after.png)
 
 ## 版本紀錄
 + v0.1 (2018.05.25-06.01)：圖書管理介面初完成。
 + v0.2 (2018.06.01-06.13)：會員管理介面初完成。
 + v0.3 (2018.06.14-06.20)：借還書處理介面初完成。
 + v0.3.1 (2018.06.20-06.22)：首頁初完成，並稍作 debug。
 + v0.4 (2018.06.22-06.25)：團體會員管理介面初完成，並略微 debug。
 + v0.5 (2018.06.30-07.01)：主要加入以下功能：
 >1. 每日檢查會員資格並自動修改各資料表；
 >2. 過期會員不得借書；
 >3. 未繳費但在寬限期內會員借書時顯示警告訊息。
 + v1.0 (2018.07.03-07.05)：在首頁加入逾期未繳費但尚在寬限期內之會員名單，並可藉由按鈕與逾期借書名單互相切換。
 + v1.1 (2018.07.06)：增加以下功能：
 >1. 新增會員資料的彈窗開啟時，自動 focus 到會號欄位；
 >2. 配合條碼掃描器，調整部分功能。
 + v1.2 (2018.07.12)：增加以下功能：
 >1. 配合條碼掃描器，增加軟體 debounce 功能（使用 JavaScript）；
 >2. 借書額度可變，工作人員的額度上調至一般會員的 3 倍。
 + v1.3 (2018.07.14)：運用 filter_var 防範隱碼攻擊。
 + v1.4 (2018.07.16)：使用 jQuery UI 加入彈窗可移動功能（draggable）。
 + v1.5 (2018.08.03)：使用條碼掃描器時有時有些書名未能即時跳出，造成書名欄被錯誤地留空，本次更新運用後端判斷查詢並修改資料庫的方式暫時解決。