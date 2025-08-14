/* global CFG_GLPI */
window.actualTime = new function() {
   this.ajax_url = CFG_GLPI.root_doc + '/plugins/actualtime/ajax/timer.php';
   var timer;
   var popup_div = '';
// Translations
   var symb_d = '%dd';
   var symb_day = '%d day';
   var symb_days = '%d days';
   var symb_h = '%dh';
   var symb_hour = '%d hour';
   var symb_hours = '%d hours';
   var symb_min = '%dmin';
   var symb_minute = '%d minute';
   var symb_minutes = '%d minutes';
   var symb_s = '%ds';
   var symb_second = '%d second';
   var symb_seconds = '%d seconds';
   var text_pause = 'Pause';
   var text_restart = 'Restart';
   var text_done = 'Done';
   var toast = null;
   var modal = null;

   this.showTaskForm = function(e) {
      e.preventDefault();
      if (modal == null) {
         var html = `<div class="modal fade" id="modal_actualtime" role="dialog">';
            <div class="modal-dialog modal-lg">
               <div id="modal_content" class="modal-content">
               </div>
            </div>
         </div>`;
         $('body').append(html);
         modal = new bootstrap.Modal(document.getElementById('modal_actualtime'),{});
      }
      $("#modal_content").load(this.ajax_url + '?showform=true');
      modal.show();
   }

   this.timeToText = function(time, format) {
      var days = 0;
      var hours = 0;
      var minutes = 0;
      var distance = time;
      var seconds = distance % 60;
      distance -= seconds;
      var text = (format == 3 ? (seconds > 1 ? symb_seconds : symb_second) : symb_s).replace('%d', seconds);
      ;
      if (distance > 0) {
         minutes = (distance % 3600) / 60;
         distance -= minutes * 60;
         text = (format == 3 ? (minutes > 1 ? symb_minutes : symb_minute) : symb_min).replace('%d', minutes) + ' ' + text;
         if (distance > 0) {
            if (format == 2) {
               hours = distance / 3600;
               if (minutes < 10) {
                  minutes = '0' + minutes;
               }
               return symb_h.replace('%d', hours) + (seconds > 0 ? symb_min.replace('%d', minutes) + symb_s.replace('%d', (seconds < 10 ? '0' : '') + seconds) : minutes);
            }
            hours = (distance % 86400) / 3600;
            distance -= hours * 3600;
            text = (format == 3 ? (hours > 1 ? symb_hours : symb_hour) : symb_h).replace('%d', hours) + ' ' + text;
            if (distance > 0) {
               days = distance / 86400;
               text = (format == 3 ? (days > 1 ? symb_days : symb_day) : symb_d).replace('%d', days) + ' ' + text;
            }
         }
      }
      return text;
   }

   this.showTimerPopup = function(id, link, name) {
      // only if enabled in settings
      if (popup_div && toast != null) {
         popup_div = popup_div.replace(/%t/g, id);
         popup_div = popup_div.replace(/%l/g, link);
         popup_div = popup_div.replace(/%n/g, name);
         $("#toast_body").html(popup_div);
         toast.show();
      }
   }

   this.startCount = function(task, time) {
      timer = setInterval(function () {
         time += 1;
         var timestr = window.actualTime.timeToText(time, 1);
         $("[id^='actualtime_timer_" + task + "_']").text(timestr);
         $("#toast_body span").text(timestr);
      }, 1000);
   }

   this.endCount = function() {
      clearInterval(timer);
   }

   this.fillCurrentTime = function(task, time) {
      var timestr = window.actualTime.timeToText(time, 1);
      $("[id^='actualtime_timer_" + task + "_']").text(timestr);
   }

   this.pressedButton = function(task, itemtype, val) {
      const config = this.getClockifyConfig();
      let clockifyTimeEntry = null;
      
      // Get ticket title for Clockify description
      const ticketTitle = document.title || `Task #${task}`;
      const description = `GLPI Task #${task} - ${ticketTitle}`;

      jQuery.ajax({
         type: "POST",
         url: this.ajax_url,
         dataType: 'json',
         data: {action: val, task_id: task, itemtype: itemtype},
         success: function (result) {
            if (result['type'] == 'info') {
               if (val == 'start') {
                  // Start Clockify timer if configured
                  if (config.apiKey && config.workspaceId) {
                     window.actualTime.startClockifyTimer(description)
                        .then(timeEntry => {
                           clockifyTimeEntry = timeEntry;
                           console.log('Clockify timer started:', timeEntry.id);
                           // Store time entry ID for later use
                           sessionStorage.setItem(`clockify_timer_${task}`, timeEntry.id);
                        })
                        .catch(error => {
                           console.error('Failed to start Clockify timer:', error);
                        });
                  }
                  
                  window.actualTime.startCount(task, result['time']);
                  $("[id^='actualtime_timer_" + task + "_']").css('color', 'red');
                  $("[id^='actualtime_button_" + task + "_1_']").attr('value', text_pause).attr('action', 'pause').css('background-color', 'orange').prop('disabled', false);
                  $("[id^='actualtime_button_" + task + "_1_']").html('<span>' + text_pause + '</span>');
                  $("[id^='actualtime_button_" + task + "_2_']").attr('action', 'end').css('background-color', 'red').prop('disabled', false);
                  window.actualTime.showTimerPopup(result['parent_id'], result['link'], result['name']);
                  $("[id^='actualtime_faclock_" + task + "_']").addClass('fa-clock').css('color', 'red');
                  return;
               } else if ((val == 'end') || (val == 'pause')) {
                  // Stop Clockify timer if it was started
                  const clockifyTimerId = sessionStorage.getItem(`clockify_timer_${task}`);
                  if (clockifyTimerId && config.apiKey && config.workspaceId) {
                     window.actualTime.stopClockifyTimer(clockifyTimerId)
                        .then(() => {
                           console.log('Clockify timer stopped');
                           sessionStorage.removeItem(`clockify_timer_${task}`);
                        })
                        .catch(error => {
                           console.error('Failed to stop Clockify timer:', error);
                        });
                  }
                  
                  window.actualTime.endCount();
                  //$("#actualtime_popup").remove();
                  toast.hide();
                  // Update all forms of this task (normal and modal)
                  $("[id^='actualtime_timer_" + task + "_']").css('color', 'black');
                  $("[id^='actualtime_faclock_" + task + "_']").css('color', 'black');
                  var timestr = window.actualTime.timeToText(result['time'], 1);
                  $("[id^='actualtime_timer_" + task + "_']").text(timestr);
                  $("[id^='actualtime_segment_" + task + "_']").html(result['segment']);
                  if (val == 'end') {
                     // Update state fields also (as Done)
                     $("select[name='state']").attr('data-track-changes', '');
                     $("span.state.state_1[onclick='change_task_state(" + task + ", this)']").attr('title', text_done).toggleClass('state_1 state_2');
                     $("input[type='hidden'][name='id'][value='" + task + "']").closest("div[data-itemtype='"+itemtype+"'][data-items-id='"+task+"']").find("select[name='state']").val(2).trigger('change');
                     $("select[name='state']").removeAttr('data-track-changes');
                     $("[id^='actualtime_button_" + task + "_']").attr('action', '').css('background-color', 'gray').prop('disabled', true);
                     if (typeof result["task_time"] !== 'undefined' && result["task_time"] != 0) {
                        var actiontime = $("input[type='hidden'][name='id'][value='" + task + "']").closest("div[data-itemtype='"+itemtype+"'][data-items-id='"+task+"']").find("select[name='actiontime']");
                        actiontime.attr('data-track-changes', '');
                        actiontime.val(result['task_time']).trigger('change');
                        actiontime.removeAttr('data-track-changes');
                        $("div[data-itemtype='"+itemtype+"'][data-items-id='"+task+"'] span.actiontime").text(window.actualTime.timeToText(result['task_time'], 1));
                     }
                  } else {
                     $("[id^='actualtime_button_" + task + "_1_']").attr('value', text_restart).attr('action', 'start').css('background-color', 'green').prop('disabled', false);
                     $("[id^='actualtime_button_" + task + "_1_']").html('<span>' + text_restart + '</span>');
                  }
               }
            }
            switch (result['type']) {
               case 'warning':
                  var title = __('Warning');
                  var css_class = 'bg-warning';
                  break;
               case 'info':
                  var title = _n("Information", "Informations", 1);
                  var css_class = 'bg-info';
                  break;
               default:
                  var title = __('Error');
                  var css_class = 'bg-danger';
                  break;
            }
            toast_id++;

            const html = `<div class='toast-container bottom-0 end-0 p-3 messages_after_redirect'>
               <div id='toast_js_${toast_id}' class='toast border-0 animate_animated animate__delay-2s animate__slow' role='alert' aria-live='assertive' aria-atomic='true'>
                  <div class='toast-header ${css_class} text-white'>
                     <strong class='me-auto'>${title}</strong>
                     <button type='button' class='btn-close' data-bs-dismiss='toast' aria-label='${__('Close')}'></button>
                  </div>
                  <div class='toast-body'>
                     ${result['message']}
                  </div>
               </div>
            </div>`;
            $('body').append(html);

            const toasttemp = new bootstrap.Toast(document.querySelector('#toast_js_' + toast_id), {
               delay: 10000,
            });
            toasttemp.show();
         }
      });
   }

   this.init = function(ajax_url) {
      window.actualTime.ajax_url = ajax_url;
      if (!$("#toast_actualtime").length) {
         const html = `<div class='toast-container bottom-0 start-0 p-3 messages_after_redirect'  id='toast_actualtime'>
            <div class='toast border-0 animate__animated animate__tada animate__delay-2s animate__slow' role='alert' aria-live='assertive' aria-atomic='true'>
               <div class='toast-header bg-info text-white'>
                  <strong class='me-auto'>${_n('Information', 'Informations', 1)}</strong>
                  <button type='button' class='btn-close' data-bs-dismiss='toast' aria-label='${__('Close')}'></button>
               </div>
               <div id='toast_body' class='toast-body'></div>
            </div>
         </div>`;
         $('body').append(html);
         toast = new bootstrap.Toast(document.querySelector('#toast_actualtime .toast:not(.show)'), {autohide:false});
      }

      // Create permanent popup for timer control (if enabled)
      jQuery.ajax({
         type: 'GET',
         url: window.actualTime.ajax_url + '?getconfig',
         dataType: 'json',
         success: function (result) {
            if (result.show_permanent_popup) {
               window.actualTime.createTimerPopup();
            }
         },
         error: function() {
            // Default: create popup if we can't get config
            window.actualTime.createTimerPopup();
         }
      });

      // Initialize
      jQuery.ajax({
         type: 'GET',
         url: window.actualTime.ajax_url + '?footer',
         dataType: 'json',
         success: function (result) {
            symb_d = result['symb_d'];
            symb_day = result['symb_day'];
            symb_days = result['symb_days'];
            symb_h = result['symb_h'];
            symb_hour = result['symb_hour'];
            symb_hours = result['symb_hours'];
            symb_min = result['symb_min'];
            symb_minute = result['symb_minute'];
            symb_minutes = result['symb_minutes'];
            symb_s = result['symb_s'];
            symb_second = result['symb_second'];
            symb_seconds = result['symb_seconds'];
            text_warning = result['text_warning'];
            text_pause = result['text_pause'];
            text_restart = result['text_restart'];
            text_done = result['text_done'];
            popup_div = result['popup_div'];

            if (result['parent_id']) {
               window.actualTime.startCount(result['task_id'], result['time']);
               window.actualTime.showTimerPopup(result['parent_id'], result['link'], result['name']);
               // Update permanent popup with active timer
               window.actualTime.updatePermanentPopup(result['task_id'], result['time'], true);
            }
         }
      });
   }

   this.createTimerPopup = function() {
      // Remove existing popup if any
      const existingPopup = document.getElementById('actualtime-permanent-popup');
      if (existingPopup) {
         existingPopup.remove();
      }

      // Get current ticket information
      const ticketInfo = this.getCurrentTicketInfo();
      
      const popup = document.createElement('div');
      popup.id = 'actualtime-permanent-popup';
      popup.className = 'actualtime-popup';
      
      const title = ticketInfo.ticketId 
         ? `#${ticketInfo.ticketId} - ${ticketInfo.ticketTitle}`
         : 'ActualTime Timer';

      popup.innerHTML = `
         <div class="actualtime-header">
            <span class="actualtime-title">${title}</span>
            <div class="actualtime-controls">
               <button class="actualtime-minimize" title="Minimizar">−</button>
               <button class="actualtime-close" title="Fechar">×</button>
            </div>
         </div>
         <div class="actualtime-content">
            <div class="actualtime-timer-display">
               <span class="timer-text">00:00:00</span>
            </div>
            <div class="actualtime-buttons">
               <button class="actualtime-start-btn" title="Iniciar cronômetro">
                  <span class="btn-icon">▶</span>
                  <span class="btn-text">Iniciar</span>
               </button>
               <button class="actualtime-stop-btn" title="Parar cronômetro" style="display: none;">
                  <span class="btn-icon">⏸</span>
                  <span class="btn-text">Parar</span>
               </button>
            </div>
            <div class="actualtime-status">Pronto para iniciar</div>
            <div class="actualtime-task-description" style="display: none;">
               <textarea placeholder="Descrição da tarefa..." rows="3"></textarea>
            </div>
         </div>
      `;

      // Apply CSS styles
      popup.style.cssText = `
         position: fixed;
         top: 20px;
         right: 20px;
         width: 300px;
         background: #fff;
         border: 1px solid #ddd;
         border-radius: 8px;
         box-shadow: 0 4px 12px rgba(0,0,0,0.15);
         z-index: 10000;
         font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
         font-size: 14px;
      `;

      document.body.appendChild(popup);
      
      // Apply styles to internal elements
      this.applyPopupStyles(popup);
      
      // Add event listeners
      this.addPopupEventListeners(popup, ticketInfo);
   }

   this.applyPopupStyles = function(popup) {
      const header = popup.querySelector('.actualtime-header');
      header.style.cssText = `
         background: #2E7D32;
         color: white;
         padding: 12px 16px;
         border-radius: 8px 8px 0 0;
         display: flex;
         justify-content: space-between;
         align-items: center;
         cursor: move;
         user-select: none;
      `;

      const title = popup.querySelector('.actualtime-title');
      title.style.cssText = `
         font-weight: 600;
         font-size: 13px;
         white-space: nowrap;
         overflow: hidden;
         text-overflow: ellipsis;
         max-width: 200px;
      `;

      const controls = popup.querySelector('.actualtime-controls');
      controls.style.cssText = `
         display: flex;
         gap: 8px;
      `;

      const controlButtons = popup.querySelectorAll('.actualtime-minimize, .actualtime-close');
      controlButtons.forEach(btn => {
         btn.style.cssText = `
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
         `;
      });

      const content = popup.querySelector('.actualtime-content');
      content.style.cssText = `
         padding: 16px;
      `;

      const timerDisplay = popup.querySelector('.actualtime-timer-display');
      timerDisplay.style.cssText = `
         text-align: center;
         margin-bottom: 16px;
      `;

      const timerText = popup.querySelector('.timer-text');
      timerText.style.cssText = `
         font-size: 24px;
         font-weight: bold;
         color: #2E7D32;
         font-family: monospace;
      `;

      const buttonsContainer = popup.querySelector('.actualtime-buttons');
      buttonsContainer.style.cssText = `
         display: flex;
         gap: 8px;
         margin-bottom: 12px;
      `;

      const buttons = popup.querySelectorAll('.actualtime-start-btn, .actualtime-stop-btn');
      buttons.forEach(btn => {
         btn.style.cssText = `
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
         `;
      });

      const startBtn = popup.querySelector('.actualtime-start-btn');
      startBtn.style.background = '#4CAF50';
      startBtn.style.color = 'white';

      const stopBtn = popup.querySelector('.actualtime-stop-btn');
      stopBtn.style.background = '#f44336';
      stopBtn.style.color = 'white';

      const status = popup.querySelector('.actualtime-status');
      status.style.cssText = `
         text-align: center;
         font-size: 12px;
         color: #666;
         margin-bottom: 8px;
      `;

      const textarea = popup.querySelector('textarea');
      textarea.style.cssText = `
         width: 100%;
         border: 1px solid #ddd;
         border-radius: 4px;
         padding: 8px;
         font-size: 13px;
         resize: vertical;
         margin-top: 12px;
      `;
   }

   this.addPopupEventListeners = function(popup, ticketInfo) {
      const minimizeBtn = popup.querySelector('.actualtime-minimize');
      const closeBtn = popup.querySelector('.actualtime-close');
      const startBtn = popup.querySelector('.actualtime-start-btn');
      const stopBtn = popup.querySelector('.actualtime-stop-btn');
      const content = popup.querySelector('.actualtime-content');
      
      let isMinimized = false;
      let startTime = null;
      let timerInterval = null;

      // Minimize/maximize functionality
      minimizeBtn.addEventListener('click', () => {
         isMinimized = !isMinimized;
         if (isMinimized) {
            content.style.display = 'none';
            popup.style.height = 'auto';
            minimizeBtn.textContent = '+';
            minimizeBtn.title = 'Maximizar';
         } else {
            content.style.display = 'block';
            minimizeBtn.textContent = '−';
            minimizeBtn.title = 'Minimizar';
         }
      });

      // Close popup
      closeBtn.addEventListener('click', () => {
         if (timerInterval) {
            clearInterval(timerInterval);
         }
         popup.remove();
      });

      // Start timer
      startBtn.addEventListener('click', () => {
         if (ticketInfo.ticketId) {
            // If we're on a ticket page, create task automatically
            this.createTaskAndStartTimer(ticketInfo, popup);
         } else {
            // Show description input for generic timer
            this.showDescriptionInput(popup);
         }
      });

      // Stop timer
      stopBtn.addEventListener('click', () => {
         this.stopTimerWithDescription(popup);
      });

      // Make popup draggable
      this.makePopupDraggable(popup);
   }

   this.getCurrentTicketInfo = function() {
      let ticketId = null;
      let ticketTitle = '';

      // Method 1: Check URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      if (window.location.pathname.includes('/ticket.form.php')) {
         ticketId = urlParams.get('id');
      }

      // Method 2: Check for hidden input with ticket ID
      if (!ticketId) {
         const hiddenInput = document.querySelector('input[name="id"][value]');
         if (hiddenInput && window.location.pathname.includes('ticket')) {
            ticketId = hiddenInput.value;
         }
      }

      // Method 3: Check page title for ticket number
      if (!ticketId && document.title) {
         const titleMatch = document.title.match(/Chamado.*?(\d+)/i);
         if (titleMatch) {
            ticketId = titleMatch[1];
         }
      }

      // Method 4: Look for ticket title in common places
      if (ticketId) {
         // Try to find ticket title in various elements
         const titleSelectors = [
            'input[name="name"]',
            '.card-title h1',
            '.page-title',
            'h1',
            '.accordion-header h2'
         ];
         
         for (const selector of titleSelectors) {
            const element = document.querySelector(selector);
            if (element) {
               const text = element.value || element.textContent || '';
               if (text.trim() && !text.includes('Ticket') && !text.includes('Chamado')) {
                  ticketTitle = text.trim();
                  break;
               }
            }
         }
      }

      // Fallback: Use page title or URL
      if (!ticketTitle) {
         if (document.title && !document.title.includes('GLPI')) {
            ticketTitle = document.title;
         } else {
            ticketTitle = 'Página atual';
         }
      }

      // Clean up title
      if (ticketTitle) {
         // Remove common prefixes
         ticketTitle = ticketTitle.replace(/^(Chamado|Ticket)\s*[-#]?\s*/i, '');
         // Limit length
         if (ticketTitle.length > 50) {
            ticketTitle = ticketTitle.substring(0, 47) + '...';
         }
      }

      console.log('Detected ticket info:', { ticketId, ticketTitle, url: window.location.href });
      
      return { ticketId, ticketTitle };
   }

   this.createTaskAndStartTimer = function(ticketInfo, popup) {
      const defaultDescription = `Trabalho no ticket #${ticketInfo.ticketId}`;
      
      // Create task and start timer via new AJAX endpoint
      jQuery.ajax({
         type: 'POST',
         url: CFG_GLPI.root_doc + '/plugins/actualtime/ajax/createtask.php',
         dataType: 'json',
         data: {
            action: 'create_task_and_start_timer',
            ticket_id: ticketInfo.ticketId,
            description: defaultDescription
         },
         success: (response) => {
            if (response.type === 'info') {
               // Store the task ID for later use
               popup.dataset.taskId = response.task_id;
               popup.dataset.itemType = 'TicketTask';
               
               // Start the popup timer display
               this.startPopupTimer(popup, defaultDescription);
               
               console.log('Task created and timer started:', response.task_id);
            } else {
               console.error('Error:', response.message);
               // Fallback: start generic timer
               this.startPopupTimer(popup, defaultDescription);
            }
         },
         error: (error) => {
            console.error('AJAX Error creating task:', error);
            // Fallback: start generic timer
            this.startPopupTimer(popup, defaultDescription);
         }
      });
   }

   this.showDescriptionInput = function(popup) {
      const descriptionDiv = popup.querySelector('.actualtime-task-description');
      descriptionDiv.style.display = 'block';
      
      const textarea = descriptionDiv.querySelector('textarea');
      textarea.focus();
      
      // Add confirm button
      const confirmBtn = document.createElement('button');
      confirmBtn.textContent = 'Confirmar';
      confirmBtn.style.cssText = `
         background: #4CAF50;
         color: white;
         border: none;
         padding: 6px 12px;
         border-radius: 4px;
         cursor: pointer;
         margin-top: 8px;
      `;
      
      confirmBtn.addEventListener('click', () => {
         const description = textarea.value.trim();
         if (description) {
            descriptionDiv.style.display = 'none';
            this.startPopupTimer(popup, description);
         }
      });
      
      descriptionDiv.appendChild(confirmBtn);
   }

   this.startPopupTimer = function(popup, description = '') {
      const startBtn = popup.querySelector('.actualtime-start-btn');
      const stopBtn = popup.querySelector('.actualtime-stop-btn');
      const status = popup.querySelector('.actualtime-status');
      const timerText = popup.querySelector('.timer-text');
      
      const startTime = Date.now();
      
      // Start Clockify timer if configured
      const config = this.getClockifyConfig();
      if (config.apiKey && config.workspaceId && description) {
         this.startClockifyTimer(description)
            .then(timeEntry => {
               console.log('Clockify timer started:', timeEntry.id);
               popup.dataset.clockifyTimeEntry = timeEntry.id;
            })
            .catch(error => {
               console.error('Failed to start Clockify timer:', error);
            });
      }
      
      // Update UI
      startBtn.style.display = 'none';
      stopBtn.style.display = 'flex';
      status.textContent = 'Cronômetro ativo';
      status.style.color = '#4CAF50';
      
      // Start timer display
      const timerInterval = setInterval(() => {
         const elapsed = Date.now() - startTime;
         const hours = Math.floor(elapsed / 3600000);
         const minutes = Math.floor((elapsed % 3600000) / 60000);
         const seconds = Math.floor((elapsed % 60000) / 1000);
         
         timerText.textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
      }, 1000);
      
      // Store timer data
      popup.dataset.timerInterval = timerInterval;
      popup.dataset.startTime = startTime;
      popup.dataset.description = description;
   }

   this.stopTimerWithDescription = function(popup) {
      const descriptionDiv = popup.querySelector('.actualtime-task-description');
      const textarea = descriptionDiv.querySelector('textarea');
      
      // Show description input for final task description
      descriptionDiv.style.display = 'block';
      textarea.placeholder = 'Descrição final da tarefa...';
      textarea.focus();
      
      // Clear existing confirm button
      const existingBtn = descriptionDiv.querySelector('button');
      if (existingBtn) existingBtn.remove();
      
      const confirmBtn = document.createElement('button');
      confirmBtn.textContent = 'Finalizar';
      confirmBtn.style.cssText = `
         background: #f44336;
         color: white;
         border: none;
         padding: 6px 12px;
         border-radius: 4px;
         cursor: pointer;
         margin-top: 8px;
      `;
      
      confirmBtn.addEventListener('click', () => {
         const finalDescription = textarea.value.trim();
         this.finalizeTimer(popup, finalDescription);
      });
      
      descriptionDiv.appendChild(confirmBtn);
   }

   this.finalizeTimer = function(popup, finalDescription) {
      const timerInterval = popup.dataset.timerInterval;
      const startTime = popup.dataset.startTime;
      const taskId = popup.dataset.taskId;
      const itemType = popup.dataset.itemType;
      const clockifyTimeEntry = popup.dataset.clockifyTimeEntry;
      
      if (timerInterval) {
         clearInterval(parseInt(timerInterval));
      }
      
      const elapsed = Date.now() - parseInt(startTime);
      const durationSeconds = Math.floor(elapsed / 1000);
      
      // Stop Clockify timer if it was started
      const config = this.getClockifyConfig();
      if (clockifyTimeEntry && config.apiKey && config.workspaceId) {
         this.stopClockifyTimer(clockifyTimeEntry)
            .then(() => {
               console.log('Clockify timer stopped');
            })
            .catch(error => {
               console.error('Failed to stop Clockify timer:', error);
            });
      }
      
      // If we have a real task, finalize it via AJAX
      if (taskId && itemType) {
         jQuery.ajax({
            type: 'POST',
            url: CFG_GLPI.root_doc + '/plugins/actualtime/ajax/createtask.php',
            dataType: 'json',
            data: {
               action: 'finish_task_with_description',
               task_id: taskId,
               final_description: finalDescription,
               duration_seconds: durationSeconds
            },
            success: (response) => {
               console.log('Task finalized:', response);
               this.updateUIAfterStop(popup);
            },
            error: (error) => {
               console.error('Error finalizing task:', error);
               this.updateUIAfterStop(popup);
            }
         });
      } else {
         // Generic timer - just log locally
         console.log('Generic timer completed:', {
            duration: durationSeconds + ' seconds',
            description: finalDescription
         });
         this.updateUIAfterStop(popup);
      }
   }

   this.updateUIAfterStop = function(popup) {
      const startBtn = popup.querySelector('.actualtime-start-btn');
      const stopBtn = popup.querySelector('.actualtime-stop-btn');
      const status = popup.querySelector('.actualtime-status');
      const timerText = popup.querySelector('.timer-text');
      const descriptionDiv = popup.querySelector('.actualtime-task-description');
      
      startBtn.style.display = 'flex';
      stopBtn.style.display = 'none';
      status.textContent = 'Timer finalizado';
      status.style.color = '#666';
      descriptionDiv.style.display = 'none';
      
      // Reset timer display
      timerText.textContent = '00:00:00';
      
      // Clear stored data
      delete popup.dataset.timerInterval;
      delete popup.dataset.startTime;
      delete popup.dataset.taskId;
      delete popup.dataset.itemType;
      delete popup.dataset.clockifyTimeEntry;
      
      // Clear textarea
      const textarea = descriptionDiv.querySelector('textarea');
      if (textarea) {
         textarea.value = '';
      }
   }

   this.makePopupDraggable = function(popup) {
      const header = popup.querySelector('.actualtime-header');
      let isDragging = false;
      let dragOffset = { x: 0, y: 0 };
      
      header.addEventListener('mousedown', (e) => {
         isDragging = true;
         dragOffset.x = e.clientX - popup.offsetLeft;
         dragOffset.y = e.clientY - popup.offsetTop;
         
         document.addEventListener('mousemove', onMouseMove);
         document.addEventListener('mouseup', onMouseUp);
      });
      
      function onMouseMove(e) {
         if (isDragging) {
            popup.style.left = (e.clientX - dragOffset.x) + 'px';
            popup.style.top = (e.clientY - dragOffset.y) + 'px';
            popup.style.right = 'auto';
         }
      }
      
      function onMouseUp() {
         isDragging = false;
         document.removeEventListener('mousemove', onMouseMove);
         document.removeEventListener('mouseup', onMouseUp);
      }
   }

   this.updatePermanentPopup = function(taskId, time, isActive) {
      const popup = document.getElementById('actualtime-permanent-popup');
      if (!popup) return;
      
      const timerText = popup.querySelector('.timer-text');
      const startBtn = popup.querySelector('.actualtime-start-btn');
      const stopBtn = popup.querySelector('.actualtime-stop-btn');
      const status = popup.querySelector('.actualtime-status');
      
      if (isActive) {
         const timeStr = this.timeToText(time, 1);
         timerText.textContent = timeStr;
         startBtn.style.display = 'none';
         stopBtn.style.display = 'flex';
         status.textContent = 'Cronômetro ativo';
         status.style.color = '#4CAF50';
      } else {
         timerText.textContent = '00:00:00';
         startBtn.style.display = 'flex';
         stopBtn.style.display = 'none';
         status.textContent = 'Pronto para iniciar';
         status.style.color = '#666';
      }
   }

   // Adicionar funções para integração com a API do Clockify
   this.getClockifyConfig = function() {
      // Fetch configuration from server with user-specific API key
      return fetch('./plugins/actualtime/ajax/timer.php?action=get_clockify_config')
         .then(response => response.json())
         .then(config => {
            return {
               apiKey: config.api_key,
               workspaceId: config.workspace_id
            };
         })
         .catch(error => {
            console.error('Error getting Clockify config:', error);
            return {
               apiKey: '',
               workspaceId: ''
            };
         });
   };

   this.startClockifyTimer = function(description) {
      return this.getClockifyConfig().then(config => {
         if (!config.apiKey || !config.workspaceId) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
         }

         const data = {
            start: new Date().toISOString(),
            description: description
         };

         return fetch(`https://api.clockify.me/api/v1/workspaces/${config.workspaceId}/time-entries`, {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json',
               'X-Api-Key': config.apiKey
            },
            body: JSON.stringify(data)
         }).then(response => {
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
         });
      });
   };

   this.stopClockifyTimer = function(timeEntryId) {
      return this.getClockifyConfig().then(config => {
         if (!config.apiKey || !config.workspaceId) {
            console.error('Clockify: Configurações não encontradas');
            return Promise.reject('Configurações do Clockify não encontradas');
         }

         // First get current user to obtain userId
         return fetch('https://api.clockify.me/api/v1/user', {
            method: 'GET',
            headers: {
               'Content-Type': 'application/json',
               'X-Api-Key': config.apiKey
            }
         }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
         }).then(user => {
            // Now stop the timer using the correct endpoint
            return fetch(`https://api.clockify.me/api/v1/workspaces/${config.workspaceId}/user/${user.id}/time-entries`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Api-Key': config.apiKey
                },
                body: JSON.stringify({ end: new Date().toISOString() })
            });
         }).then(response => {
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
         });
      });
   };
}();

$(document).ready(function(){
   var url = CFG_GLPI.root_doc+"/"+GLPI_PLUGINS_PATH.actualtime+"/ajax/timer.php";
   window.actualTime.init(url);
});