var majaxModule=(function (my) {
        
    const majaxRender = {   
        customType : "",
        getType: function() {
            if (majaxRender.customType=="") {                
                majaxRender.customType=jQuery('input[name="type"]').val();
            } 
            return majaxRender.customType;
        },     
        postTemplate: (id,title,name,content,url,meta,image,single) => { 
            let metaOut=[];
            for (let n=0;n<5;n++) {
                metaOut[n]="";    
            }            
        
            for (const property in meta) {
                let metaIcon=my.metaMisc.icons[property];
                let displayOrder=my.metaMisc.displayorder[property];
                if (typeof metaIcon!== 'undefined' && metaIcon!="") metaIcon=`<img src='${metaIcon}' />`;
                else metaIcon=`<span>${property}</span>`;	
                if (displayOrder<20) {
                    metaOut[0]=metaOut[0] + `<div class='col meta'>${metaIcon}${meta[property]}</div>`;
                }
                if (displayOrder>=20 && displayOrder<=30) {                    		
                    let formattedVal1=my.metaMisc.formatMetaVal(meta[property],0,my.metaMisc.fieldformat[property],"toFormat",true);
                    let formattedVal2=my.metaMisc.formatMetaVal(Math.ceil(meta[property]*1.21),0,my.metaMisc.fieldformat[property],"toFormat",true);
                    metaOut[1]=metaOut[1] + `  
                    <div class='col-sm-6 price'>
                        Cena bez DPH / měsíc 
                        <div class='row'>
                            <div class='col priceTag'>
                                ${formattedVal1}
                            </div>
                        </div> 
                    </div>`;
                    metaOut[2]=metaOut[2] + `
                    <div class='col-sm-6 price'>
                        Cena včetně DPH / měsíc 
                        <div class='row'>
                            <div class='col priceTag'>
                             ${formattedVal2}
                            </div>
                        </div> 
                    </div>`;
                }
                if (displayOrder>30 && displayOrder<=40) {
                    let propVal=meta[property];
                    if (propVal == null) propVal="neuvedeno";
                    metaOut[3]=metaOut[3] + `
                    <div class='col-sm-3'>
                        <span>${my.metaMisc.title[property]}</span>
                        <div class='row'>
                            <span>
                             ${propVal}
                            </span>
                        </div> 
                    </div>`
                }
            }	
            if (image!="") {
                image=`<img src='${image}' />
                     <div class='stripes stripe1'>Převodovka - manuál</div>
                     <div class='stripes stripe2'>Dálniční známka pro ČR</div>   
                `;
            }            
            if (typeof single !== 'undefined' ) {
                 //single                      
                 return my.majaxViewComponents.singleDefaultShow(id,title,content,image,metaOut);                      
            }
            else {
                //multi default    
                return my.majaxViewComponents.multiDefaultShow(id,name,content,image,metaOut);                  
            }            
        },
        postTemplateEmpty: (id,content) => { 
            let metaOut="";	
            return(
            `
            <div class='majaxout' id='majaxout${id}'>
            ${content}
            </div>
            `);
        },
        postTemplateCounts: (id,meta) => {		
            if (meta["meta_key"]=="clearall" && meta["meta_value"]=="clearall") {
                //first record- clearall
                my.mCounts.clearAll();		
                return;
            }				
            //update inputs counts
            if (meta["meta_key"]=="endall" && meta["meta_value"]=="endall") { 
                //last  record- processing
                for (let key in my.mCounts.metaCounts) {
                    let mMeta=my.mCounts.metaCounts[key];
                    let filterElement=jQuery('#custField'+mMeta.metaKey);
                    let labelFor=jQuery('label[for="custField'+mMeta.metaKey);	
                    let cnt="("+mMeta.rowsCount+")";	
                    if (typeof filterElement[0] !== 'undefined' && filterElement[0].type=="checkbox" && mMeta.metaName == "1") {
                        let followingElement=labelFor.next();
                        let spanCounter="<span class='counter'>"+cnt+"</span>";
                        if (followingElement.length>0 && followingElement[0].tagName=="SPAN") {
                            followingElement.text(cnt);
                        } else {
                            labelFor.after(spanCounter);
                            //console.log(spanCounter);
                        }
                        
                    }
                }	
                //update selects
                jQuery(".majax-select").trigger('change.select2');	
                
                
                return;
            }
            my.mCounts.addMetaCount(my.mStrings.mNormalize(meta["meta_key"]),my.mStrings.mNormalize(meta["meta_value"]),meta["count"]);
        },
        postTemplatePagination: (pages) => {
            let content="";
            let n=0;
            let aktPage=0;
            let cntPage=0;
            for (let page in pages) { 
                if (pages[page]=="2") {
                 aktPage=n;
                }
                if (pages[page]!="pagination") n++;
            }            
            cntPage=n;
            if (cntPage==1) return "";
            let p=0;
            let url="";
            for (p=0;p<cntPage;p++) {
                if (p==0 || p==cntPage-1 || (p>aktPage-3 && p<aktPage+3) || p==aktPage) {
                    if (p!=aktPage) {
                        if (p!=0) url=my.mUrl.generateUrl("aktPage",p);
                        else url=my.mUrl.generateUrl("aktPage",null);
                        content+=`
                        <span><a data-slug='pagination' data-page='${p}' href='${url}'>${p+1}</a></span>
                        `;
                    } else {
                        content+=`
                        <span>${p+1}</span> 
                        `; 
                    }
                } 
                else {
                    if (p==aktPage-3) content+=`..`;
                    if (p==aktPage+3) content+=`..`;
                }
            }
            return(
                `    
                <div class='mpagination'>            
                ${content}                
                </div>
                `);   
        },
        sendClearFunction: (firingAction) => {
            jQuery('#majaxmain').empty();				 
            jQuery('#majaxmain').append(my.majaxViewComponents.majaxLoader);
            if (firingAction!="single_row") jQuery('.majax-loader').css('display','flex');	 
            //my.mCounts.clearCounts();	
            //nastavit vsem checkboxum a dalsim elementum nuly- neni potreba, posilaji se i nuly
            //jQuery('.counter').text("(0)");
        },
        hideLoaderAnim: () => {
            jQuery('.majax-loader').addClass('majax-loader-disappear-anim');
        },
        animateMajaxBox: (thisHtml,thisId) => {
            jQuery('#majaxmain').append(thisHtml);
            //jQuery("#majaxout"+thisId).fadeIn("slow");														
            jQuery("#majaxout"+thisId).css("display", "flex").hide().fadeIn("slow");
            //jQuery('.majax-loader').addClass('majax-loader-disappear-anim');
            majaxRender.hideLoaderAnim();
        },
        showBack: () => {     
             jQuery("#majaxform").hide();
             jQuery("#majaxback").show();
        },
        hideBack: () => { 
            jQuery("#majaxform").show();
            jQuery("#majaxback").hide();
        },
        drawResultsFunction: (thisId,jsonObj) => {	
            if (jsonObj.title=="majaxcounts") thisHtml=majaxRender.postTemplateCounts(thisId,jsonObj);
            else if (jsonObj.title=="buildInit") {
                my.metaMisc.addMetaMisc(jsonObj.misc);
                //update sliders min-max
                my.majaxSlider.initSlidersMinMax(); 
            }
            else if (jsonObj.title=="pagination") {                
                let thisHtml=majaxRender.postTemplatePagination(jsonObj);
                jQuery('#majaxmain').append(thisHtml);                
            }
            else if (jsonObj.title=="action") {                                
                majaxRender.hideLoaderAnim();                              
                jQuery('#majaxmain').append(my.majaxViewComponents.majaxContactForm.renderDefault("majaxContactForm",jsonObj.content,jsonObj.postTitle,jsonObj.postType));
                my.majaxViewComponents.majaxContactForm.initDefault("majaxContactForm");                                                    
                majaxRender.showBack();                
            }
            else if (jsonObj.title=="empty") {
                thisHtml=majaxRender.postTemplateEmpty(thisId,jsonObj.content);
                majaxRender.animateMajaxBox(thisHtml,thisId);			
            }
            else { 
                thisHtml=majaxRender.postTemplate(thisId,jsonObj.title,jsonObj.name,jsonObj.content,jsonObj.url,jsonObj.meta,jsonObj.image,jsonObj.single);
                majaxRender.animateMajaxBox(thisHtml,thisId);			
            }
         }
    }

    my.majaxRender=majaxRender;
    return my;

}(majaxModule || {} ));
