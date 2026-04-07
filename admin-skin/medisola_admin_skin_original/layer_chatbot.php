<?php
if(Session::get('manager.isSuper') != 'cs'){
    ?>
    <script>
        <?php
        echo "var shopNo = '$chatbotData[shopNo]';";
        echo "var shopMainUrl = '$chatbotData[mainDomain]';";
        echo "var shopImsiUrl = '$chatbotData[imsiDomain]';";
        echo "var userId = '$chatbotData[adminId]';";
        echo "var isSuper = '$chatbotData[isSuper]';";
        ?>
        var ic3_config = function() {
            this.page.bot.repoId = "IC"; // {required} infochatter3 저장소 아이디
            this.page.bot.agentName = "고도몰5"; // {required} infochatter3 에이전트 명
            this.page.bot.agentId = "138005"; // {required} Infochatter3 에이전트 ID
            this.page.bot.name = "NHN커머스 커밋"; // {required} ChatUI에 나타나는 상단 타이틀
            this.page.ca.caRepoId = "sh1a24c7d9by"; // {required} 채팅상담솔루션 연계 아이디
            this.page.ca.caUrl = "https://pickbot.ai/chatassist"; // {required} 채팅상담솔루션 API 주소
            this.page.chatUIUrl= "https://pickbot.ai/shopby/chatbot"; //{required} 인포채터3 챗봇 URL (테마 및 chatUI 연계)
            this.page.restAPI= "https://pickbot.ai/shopby/restapi"; //{required} 인포채터3 Rest API 주소 (자동완성 / 의견)
            this.page.shop.shopId = shopNo; //{option} NHN커머스 Shop ID
            this.page.shop.shopUrl = shopMainUrl //{option} NHN커머스 Shop URL (main)
            this.page.shop.tempShopUrl = "https://gdadmin." + shopImsiUrl + '"'; //{option} NHN커머스 Shop URL (sub)
            this.page.user.userId = userId; // {required} user id
            this.page.user.isSuper = isSuper; // {required} 최고운영자 여부 (최고운영자-y,그외-n,고객지원-cs)
            this.page.setting.width = 490; //{option} 챗봇UI의 가로 사이즈
            this.page.setting.height = 850; //{option} 챗봇 UI의 세로 사이즈
            this.page.launcher.type = 'circle-square';
            this.page.launcher.size = 43; //
            this.page.launcher.location.right.distance = 0; //{option} 챗봇 화면 맨 오른쪽으로부터 떨어진 간격
            this.page.launcher.location.bottom.distance = 0; //{option} 챗봇 화면 맨 아래쪽으로부터 떨어진 간격
        };
        $(window).on("load", function () {
            (function() {
                var doc = document,
                    embedScript = doc.createElement("script");
                embedScript.src = "https://pickbot.ai/shopby/chatbot/embed-chatbot/embed.js"; // embeded_chatbot이 설치된 주소
                embedScript.setAttribute("data-timestamp", +new Date());
                doc.body.appendChild(embedScript);
            })();
        });
    </script>
    <?php
    if($indexFl == 'y') { ?>
        <!-- 220622 고도몰5 챗봇 -->
        <div class="chatbot_wrap">
            <span class="btn_chatbot" id="ic3_thread"></span>
        </div>
    <?php } ?>
<?php } ?>